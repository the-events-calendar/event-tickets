<?php
/**
 * Regression tests for the Stripe PaymentIntent <-> order amount binding.
 *
 * Ensures a PaymentIntent created for one (e.g. lower-value) cart cannot be bound to, or used
 * to complete, a different (e.g. higher-value) local order.
 *
 * @since 5.28.5.1
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Settings_Manager;
use WP_Error;

/**
 * Class Payment_Intent_Order_Binding_Test.
 *
 * @since 5.28.5.1
 *
 * @covers \TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent::is_valid_for_order
 * @covers \TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent_Handler::update_payment_intent
 */
class Payment_Intent_Order_Binding_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;

	/**
	 * Cleans up state shared across the suite after each test.
	 *
	 * make_order_for_price() calls tribe_update_option(), which memoizes the options in an
	 * in-memory tribe var (Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME). The per-test DB
	 * rollback does not touch that cache, so the mutated options (e.g. ticket-enabled-post-types,
	 * currency) bleed into later tests and corrupt their stock assertions (EventStockTest,
	 * CapacityTest, Stock\Race_Condition_Test). Clearing the var forces a fresh read from the
	 * rolled-back DB on the next access. We also empty the shared cart this test populates.
	 *
	 * @since TBD
	 */
	public function tearDown(): void {
		tribe( Cart::class )->clear_cart();
		tribe_set_var( Tribe__Settings_Manager::OPTION_CACHE_VAR_NAME, [] );

		parent::tearDown();
	}

	/**
	 * Create a Tickets Commerce order for a single ticket at the given price.
	 *
	 * @param float $price The ticket price in major units.
	 *
	 * @return \WP_Post The created order.
	 */
	protected function make_order_for_price( float $price ): \WP_Post {
		tribe_update_option( Currency::$currency_code_option, 'USD' );
		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'page' ] );

		$post   = static::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket = $this->create_tc_ticket( $post, $price );

		// Use the singleton cart so create_from_cart reads the same items.
		tribe( Cart::class )->get_repository()->upsert_item( $ticket, 1 );

		$order = tribe( Order::class )->create_from_cart(
			tribe( Gateway::class ),
			[
				'purchaser_user_id'    => 0,
				'purchaser_full_name'  => 'SVUL103 Purchaser',
				'purchaser_first_name' => 'SVUL103',
				'purchaser_last_name'  => 'Purchaser',
				'purchaser_email'      => 'svul103-' . uniqid() . '@test.com',
			]
		);

		clean_post_cache( $order->ID );

		return $order;
	}

	/**
	 * @test
	 */
	public function should_accept_payment_intent_matching_order_total(): void {
		$order = $this->make_order_for_price( 100.0 );

		// $100.00 in USD minor units.
		$this->assertTrue(
			Payment_Intent::is_valid_for_order( [ 'id' => 'pi_test', 'amount' => '10000' ], $order ),
			'A PaymentIntent whose amount equals the order total should be accepted.'
		);
	}

	/**
	 * @test
	 */
	public function should_reject_payment_intent_with_lower_amount_than_order_total(): void {
		$order = $this->make_order_for_price( 100.0 );

		// $1.00 PaymentIntent against a $100.00 order. This is the core SVUL-103 scenario.
		$this->assertFalse(
			Payment_Intent::is_valid_for_order( [ 'id' => 'pi_cheap', 'amount' => '100' ], $order ),
			'A PaymentIntent for a smaller amount must not validate against a higher-value order.'
		);
	}

	/**
	 * @test
	 */
	public function should_reject_payment_intent_missing_amount_or_id_or_with_errors(): void {
		$order = $this->make_order_for_price( 100.0 );

		$this->assertFalse(
			Payment_Intent::is_valid_for_order( [ 'id' => 'pi_test' ], $order ),
			'A PaymentIntent without an amount must be rejected.'
		);

		$this->assertFalse(
			Payment_Intent::is_valid_for_order( [ 'amount' => '10000' ], $order ),
			'A PaymentIntent without an id must be rejected.'
		);

		$this->assertFalse(
			Payment_Intent::is_valid_for_order( [ 'id' => 'pi_test', 'amount' => '10000', 'errors' => [ 'boom' ] ], $order ),
			'A PaymentIntent carrying errors must be rejected.'
		);
	}

	/**
	 * @test
	 */
	public function should_reject_order_without_total_value(): void {
		$plain_post = get_post( static::factory()->post->create() );

		$this->assertFalse(
			Payment_Intent::is_valid_for_order( [ 'id' => 'pi_test', 'amount' => '10000' ], $plain_post ),
			'Validation must fail closed when the order has no total_value.'
		);
	}

	/**
	 * The create endpoint must refuse to bind a PaymentIntent created for a different cart.
	 *
	 * @test
	 */
	public function should_reject_create_with_payment_intent_for_a_different_cart(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$order = $this->make_order_for_price( 100.0 );

		// A real, attacker-controlled low-value PaymentIntent ($1.00).
		$cheap_payment_intent = [
			'id'                     => 'pi_cheap',
			'amount'                 => '100',
			'application_fee_amount' => '0',
			'metadata'               => [],
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $cheap_payment_intent );
		// Current cart is the $100.00 cart.
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_cheap' ] ],
			$order
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'tec-tc-gateway-stripe-payment-intent-cart-mismatch', $result->get_error_code() );
	}

	/**
	 * The create endpoint must still accept the PaymentIntent created for the current cart.
	 *
	 * @test
	 */
	public function should_accept_create_with_payment_intent_matching_current_cart(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$order = $this->make_order_for_price( 100.0 );

		$matching_payment_intent = [
			'id'                     => 'pi_match',
			'amount'                 => '10000',
			'application_fee_amount' => '0',
			'metadata'               => [],
			'client_secret'          => 'pi_match_secret',
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $matching_payment_intent );
		$this->set_class_fn_return( Payment_Intent::class, 'update', $matching_payment_intent );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_match' ] ],
			$order
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'pi_match', $result['id'] );
	}
}
