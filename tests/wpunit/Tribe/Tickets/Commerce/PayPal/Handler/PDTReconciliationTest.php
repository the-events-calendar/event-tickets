<?php

namespace Tribe\Tickets\Commerce\PayPal\Handler;

use Codeception\TestCase\WPTestCase;
use Faker\Factory;
use Generator;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Custom_Argument as Custom_Argument;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Data_API as Data_API;

/**
 * A PDT return only fulfils what the site's own checkout record says was bought and priced.
 */
class PDTReconciliationTest extends WPTestCase {

	use PayPal_Ticket_Maker;

	/**
	 * @var array<string,string|string[]> The `$_GET` superglobal before the test.
	 */
	private $get_backup;

	/**
	 * @var int|null The post the tickets belong to, created on first use.
	 */
	private $post_id;

	/**
	 * @var int|null The ticket created on first use.
	 */
	private $ticket_id;

	/**
	 * @var string The encoded `custom` argument of the last simulated return.
	 */
	private $last_custom = '';

	/**
	 * @before
	 */
	public function set_up_tribe_commerce(): void {
		$this->get_backup = $_GET;

		tribe_update_option( 'ticket-paypal-email', 'merchant@example.com' );
		tribe_update_option( 'ticket-commerce-currency-code', 'USD' );

		add_filter( 'tribe_tickets_post_types', static function () {
			return [ 'post' ];
		} );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ PayPal::class ] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );
		tribe_singleton( 'tickets.data_api', new Data_API() );

		add_filter( 'tribe_exit', static function () {
			return '__return_true';
		} );
		add_filter( 'wp_redirect', '__return_false' );
		add_filter( 'pre_wp_mail', '__return_true' );
	}

	/**
	 * @after
	 */
	public function restore_get(): void {
		$_GET = $this->get_backup;
	}

	/**
	 * @test
	 */
	public function it_should_not_complete_attendees_when_the_buyer_paid_less_than_the_cart_total(): void {
		$faker    = Factory::create();
		$price    = $faker->numberBetween( 10, 50 );
		$quantity = $faker->numberBetween( 2, 4 );

		$txn_id = $this->return_from_paypal( $price, $quantity, [ 'mc_gross' => $quantity * $price / 100 ] );

		$this->assertSame( [], $this->completed_attendees( $txn_id ), 'A token payment must not buy the full order.' );
	}

	/**
	 * @test
	 */
	public function it_should_not_complete_attendees_when_the_buyer_raised_the_quantity_above_the_cart(): void {
		$faker = Factory::create();
		$price = $faker->numberBetween( 10, 50 );

		$txn_id = $this->return_from_paypal( $price, 1, [ 'quantity1' => 3, 'mc_gross' => 3 * $price ] );

		$this->assertSame( [], $this->completed_attendees( $txn_id ), 'Tickets beyond the cart must not be issued.' );
	}

	/**
	 * @return Generator<string,array{0: array<string,mixed>}> The PayPal fields that disagree with the cart.
	 */
	public function mismatching_fields_provider(): Generator {
		yield 'item amount below the price' => [ [ 'mc_gross_1' => 0.01 ] ];
		yield 'other currency' => [ [ 'mc_currency' => 'JPY' ] ];
		yield 'other merchant' => [ [ 'receiver_email' => 'attacker@example.com' ] ];
		yield 'no receiver' => [ [ 'receiver_email' => '' ] ];
		yield 'no invoice' => [ [ 'custom' => [ 'tribe_handler' => 'tpp', 'user_id' => 0 ] ] ];
		yield 'invoice without cart' => [ [ 'custom' => [ 'tribe_handler' => 'tpp', 'user_id' => 0, 'invoice' => 'unknowninvoice' ] ] ];
		yield 'pending with a tampered amount' => [ [ 'payment_status' => 'Pending', 'mc_gross' => 0.01 ] ];
		yield 'total is not a number' => [ [ 'mc_gross' => 'free' ] ];
	}

	/**
	 * @test
	 * @dataProvider mismatching_fields_provider
	 *
	 * @param array<string,mixed> $overrides The PayPal fields that disagree with the cart.
	 */
	public function it_should_not_create_attendees_for_a_transaction_that_does_not_match_the_cart( array $overrides ): void {
		$txn_id = $this->return_from_paypal( 20, 2, $overrides );

		$this->assertSame( [], tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $txn_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_create_attendees_for_a_ticket_that_is_not_in_the_cart(): void {
		$other_ticket = $this->create_paypal_ticket( $this->factory()->post->create(), 20 );

		$txn_id = $this->return_from_paypal( 20, 1, [ 'item_number1' => "{$this->post_id}:{$other_ticket}" ] );

		$this->assertSame( [], tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $txn_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_fulfil_a_second_transaction_against_a_consumed_cart(): void {
		$first = $this->return_from_paypal( 20, 2 );

		$this->assertCount( 2, $this->completed_attendees( $first ) );

		$custom = $this->last_custom;
		$second = $this->return_from_paypal( 20, 2, [ 'custom' => $custom ], false );

		$this->assertSame( [], tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $second ) );
	}

	/**
	 * @test
	 */
	public function it_should_complete_attendees_when_the_payment_matches_the_cart(): void {
		$faker    = Factory::create();
		$price    = $faker->numberBetween( 10, 50 );
		$quantity = $faker->numberBetween( 1, 4 );

		$txn_id = $this->return_from_paypal( $price, $quantity );

		$this->assertCount( $quantity, $this->completed_attendees( $txn_id ) );
	}

	/**
	 * @return array<int,array<string,mixed>> The completed attendees of the PayPal order.
	 */
	private function completed_attendees( string $txn_id ): array {
		$attendees = tribe( 'tickets.commerce.paypal' )->get_attendees_by_order_id( $txn_id );

		return array_values( array_filter( $attendees, static function ( $attendee ) {
			return 'completed' === $attendee['order_status'];
		} ) );
	}

	/**
	 * Stores the server-side cart the checkout saves, then simulates PayPal returning the buyer with a
	 * Completed payment that matches it, except for the given, possibly tampered, fields.
	 *
	 * @param int                 $price     The ticket price.
	 * @param int                 $quantity  The quantity the site stored in its cart.
	 * @param array<string,mixed> $overrides PayPal fields replacing the matching ones; `custom` may be an array or the encoded string.
	 * @param bool                $store_cart Whether the checkout stores its cart.
	 *
	 * @return string The PayPal transaction ID.
	 */
	private function return_from_paypal( int $price, int $quantity, array $overrides = [], bool $store_cart = true ): string {
		$faker   = Factory::create();
		$txn_id  = strtoupper( $faker->bothify( '#?#?#?#?#?#?#?#?#' ) );
		$invoice = $faker->regexify( '[A-Za-z0-9]{12}' );

		if ( null === $this->post_id ) {
			$this->post_id   = $this->factory()->post->create();
			$this->ticket_id = $this->create_paypal_ticket( $this->post_id, $price );
		}

		if ( $store_cart ) {
			$cart = tribe( 'tickets.commerce.paypal.cart' );
			$cart->set_id( $invoice );
			$cart->add_item( $this->ticket_id, $quantity );
			$cart->save();
		}

		$custom = $overrides['custom'] ?? [ 'tribe_handler' => 'tpp', 'user_id' => 0, 'invoice' => $invoice ];
		$custom = is_array( $custom ) ? Custom_Argument::encode( $custom ) : $custom;
		$total  = $quantity * $price;

		$fields = array_merge( [
			'payment_status' => 'Completed',
			'txn_id'         => $txn_id,
			'first_name'     => $faker->firstName(),
			'last_name'      => $faker->lastName(),
			'payer_email'    => $faker->safeEmail(),
			'receiver_email' => 'merchant@example.com',
			'item_number1'   => "{$this->post_id}:{$this->ticket_id}",
			'quantity1'      => $quantity,
			'mc_gross'       => $total,
			'mc_gross_1'     => $total,
			'mc_currency'    => 'USD',
		], $overrides, [ 'custom' => $custom ] );

		$this->last_custom = $custom;

		$body = 'SUCCESS';
		foreach ( $fields as $key => $value ) {
			$value = is_float( $value ) ? number_format( $value, 2, '.', '' ) : $value;
			$body .= "\n{$key}=" . rawurlencode( (string) $value );
		}

		add_filter( 'pre_http_request', static function () use ( $body ) {
			return [
				'headers'  => [],
				'body'     => $body,
				'response' => [ 'code' => 200, 'message' => 'OK' ],
				'cookies'  => [],
				'filename' => null,
			];
		} );

		$_GET['tx'] = $txn_id;

		( new PDT() )->check_response();

		return $txn_id;
	}
}
