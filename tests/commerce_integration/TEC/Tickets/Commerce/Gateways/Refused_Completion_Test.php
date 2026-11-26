<?php
/**
 * A buyer who pays after their stock hold expired, and lost the seat to another checkout, is not told the order succeeded.
 *
 * @package TEC\Tickets\Commerce\Gateways
 */

namespace TEC\Tickets\Commerce\Gateways;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Square\Gateway as Square_Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Order as Square_Order;
use TEC\Tickets\Commerce\Gateways\Square\Payment_Handler;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint as Square_Endpoint;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as Stripe_Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Gateways\Stripe\REST\Order_Endpoint as Stripe_Endpoint;
use TEC\Tickets\Commerce\Order;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Tickets\Commerce\Status\Created;
use TEC\Tickets\Commerce\Stock_Validator;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Settings_Manager;
use WP_Error;
use WP_Post;
use WP_REST_Request;

class Refused_Completion_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;

	/**
	 * The purchaser every checkout uses.
	 *
	 * @var array<string,mixed>
	 */
	private const PURCHASER = [
		'purchaser_user_id'    => 0,
		'purchaser_full_name'  => 'Late Buyer',
		'purchaser_first_name' => 'Late',
		'purchaser_last_name'  => 'Buyer',
		'purchaser_email'      => 'late-buyer@test.com',
	];

	/**
	 * @var int
	 */
	private int $ticket_id;

	public function setUp(): void {
		parent::setUp();

		tribe_update_option( Currency::$currency_code_option, 'USD' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );

		$post            = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$this->ticket_id = $this->create_tc_ticket( $post, 10, [ 'tribe-ticket' => [ 'capacity' => 1 ] ] );
	}

	public function tearDown(): void {
		tribe( Cart::class )->clear_cart();
		tribe_set_var( Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME, [] );

		parent::tearDown();
	}

	/**
	 * Puts the ticket in a cart of its own.
	 *
	 * @param string $hash The cart hash.
	 */
	private function fill_cart( string $hash ): void {
		$cart = tribe( Cart::class );
		$cart->clear_cart();
		$cart->set_cart_hash( md5( $hash ) );
		$cart->get_repository()->upsert_item( $this->ticket_id, 1 );
	}

	/**
	 * Another checkout holds the only seat.
	 */
	private function hold_the_seat_elsewhere(): void {
		$this->fill_cart( 'other-checkout' );
		$held = tribe( Order::class )->create_from_cart_holding_stock( tribe( Stripe_Gateway::class ), self::PURCHASER );
		$this->assertInstanceOf( WP_Post::class, $held );
		tribe( Cart::class )->clear_cart();
	}

	/**
	 * @test
	 */
	public function should_not_report_success_for_a_stripe_payment_whose_seat_was_taken() {
		// The late buyer's order, whose stock hold expired before they paid.
		$this->fill_cart( 'late-checkout' );
		$late = tribe( Order::class )->create_from_cart( tribe( Stripe_Gateway::class ), self::PURCHASER );
		update_post_meta( $late->ID, Order::$gateway_order_id_meta_key, 'pi_late' );

		$this->hold_the_seat_elsewhere();
		$this->fill_cart( 'late-checkout' );

		$this->set_class_fn_return(
			Payment_Intent::class,
			'get',
			[
				'id'            => 'pi_late',
				'client_secret' => 'pi_late_secret',
				'status'        => 'succeeded',
				'amount'        => 1000,
				'currency'      => 'usd',
			]
		);
		$this->set_class_fn_return( Payment_Intent::class, 'is_valid_for_order', true );

		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/stripe/order/pi_late' );
		$request->set_param( 'order_id', 'pi_late' );
		$request->set_param( 'client_secret', 'pi_late_secret' );

		$result = tribe( Stripe_Endpoint::class )->handle_update_order( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec-tc-gateway-stripe-unconfirmed-order', $result->get_error_code() );
		$this->assertTrue( tribe( Cart::class )->has_items(), 'The cart of a refused order was cleared.' );
		$this->assertSame( tribe( Created::class )->get_wp_slug(), get_post_status( $late->ID ) );
	}

	/**
	 * @test
	 */
	public function should_not_send_the_database_error_of_a_failed_stock_lock_to_the_buyer() {
		$this->set_class_fn_return(
			Stock_Validator::class,
			'lock_stock',
			static function () {
				throw new DatabaseQueryException( 'SELECT ID FROM wp_posts FOR UPDATE', [ 'Lock wait timeout exceeded' ], 'Lock wait timeout exceeded on wp_posts' );
			},
			true
		);

		$this->fill_cart( 'locked-checkout' );
		$result = tribe( Order::class )->create_from_cart_holding_stock( tribe( Stripe_Gateway::class ), self::PURCHASER );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec-tc-stock-lock-failed', $result->get_error_code() );
		$json = wp_json_encode( rest_convert_error_to_response( $result )->get_data() );
		$this->assertStringNotContainsString( 'Lock wait', $json );
		$this->assertStringNotContainsString( 'wp_posts', $json );
	}

	/**
	 * @test
	 */
	public function should_not_report_success_for_a_square_payment_whose_order_could_not_complete() {
		$this->fill_cart( 'square-checkout' );

		$this->set_class_fn_return(
			Square_Order::class,
			'upsert_square_from_local_order',
			static function ( WP_Post $order ) {
				update_post_meta( $order->ID, Order::$gateway_order_id_meta_key, 'sq_order' );

				return 'sq_order';
			},
			true
		);
		$this->set_class_fn_return(
			Payment_Handler::class,
			'create_payment_for_order',
			[
				'id'         => 'sq_payment',
				'created_at' => '2026-11-23T10:00:00Z',
				'status'     => 'COMPLETED',
			]
		);
		// The stock check refuses the completion, as it does when another checkout took the seat.
		$this->set_class_fn_return( Order::class, 'modify_status', false );

		$request = new WP_REST_Request( 'POST', '/tec-tickets/v1/commerce/square/order' );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body(
			wp_json_encode(
				[
					'payment_source_id' => 'cnon:card-nonce-ok',
					'purchaser'         => [
						'name'  => 'Late Buyer',
						'email' => 'late-buyer@test.com',
					],
				]
			)
		);

		$result = tribe( Square_Endpoint::class )->handle_create_order( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'tec-tc-gateway-square-unconfirmed-order', $result->get_error_code() );
		$this->assertTrue( tribe( Cart::class )->has_items(), 'The cart of a refused order was cleared.' );
	}
}
