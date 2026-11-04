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
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Stripe_Payment_Intent_Maker;
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
 * @covers \TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent::is_valid_for_cart
 * @covers \TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent::is_bound_to_cart
 */
class Payment_Intent_Order_Binding_Test extends WPTestCase {

	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use Stripe_Payment_Intent_Maker;

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
	 * @since 5.28.5.1
	 */
	public function tearDown(): void {
		unset( $_COOKIE[ tribe( Payment_Intent_Handler::class )->get_payment_intent_cookie_name() ] );
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
			'currency'               => 'usd',
			'status'                 => 'requires_payment_method',
			'metadata'               => $this->mint_cart_binding_metadata(),
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $cheap_payment_intent );
		// Current cart is the $100.00 cart.
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$this->issue_payment_intent_to_browser( 'pi_cheap' );

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
			'currency'               => 'usd',
			'status'                 => 'requires_payment_method',
			'metadata'               => $this->mint_cart_binding_metadata(),
			'client_secret'          => 'pi_match_secret',
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $matching_payment_intent );
		$this->set_class_fn_return( Payment_Intent::class, 'update', $matching_payment_intent );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$this->issue_payment_intent_to_browser( 'pi_match' );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_match' ] ],
			$order
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'pi_match', $result['id'] );
	}

	/**
	 * A PaymentIntent already bound to another order must be refused before the intent is mutated
	 * at Stripe, so a payment made for one order cannot be rebound to a different same-priced order.
	 *
	 * @test
	 */
	public function should_reject_create_when_payment_intent_bound_to_another_order(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$paid_order = $this->make_order_for_price( 100.0 );
		$this->bind_payment_intent_to_order( 'pi_bound', $paid_order->ID );

		// A second buyer's order for the same $100.00 total.
		$second_order = $this->make_order_for_price( 100.0 );

		$shared_payment_intent = [
			'id'                     => 'pi_bound',
			'amount'                 => '10000',
			'application_fee_amount' => '0',
			'currency'               => 'usd',
			'status'                 => 'requires_payment_method',
			'metadata'               => $this->mint_cart_binding_metadata(),
			'client_secret'          => 'pi_bound_secret',
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $shared_payment_intent );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$stripe_was_mutated = false;
		$this->set_class_fn_return(
			Payment_Intent::class,
			'update',
			function () use ( &$stripe_was_mutated, $shared_payment_intent ) {
				$stripe_was_mutated = true;

				return $shared_payment_intent;
			},
			true
		);

		$this->issue_payment_intent_to_browser( 'pi_bound' );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_bound' ] ],
			$second_order
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'tec-tc-gateway-stripe-payment-intent-already-bound', $result->get_error_code() );
		$this->assertFalse(
			$stripe_was_mutated,
			'The PaymentIntent must not be updated at Stripe when it is already bound to another order.'
		);
	}

	/**
	 * The same cart retrying its own order must still succeed: the intent is bound to that order, not
	 * a different one.
	 *
	 * @test
	 */
	public function should_accept_create_when_payment_intent_bound_to_same_order(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$order = $this->make_order_for_price( 100.0 );
		$this->bind_payment_intent_to_order( 'pi_same', $order->ID );

		$payment_intent = [
			'id'                     => 'pi_same',
			'amount'                 => '10000',
			'application_fee_amount' => '0',
			'currency'               => 'usd',
			'status'                 => 'requires_payment_method',
			'metadata'               => $this->mint_cart_binding_metadata(),
			'client_secret'          => 'pi_same_secret',
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $payment_intent );
		$this->set_class_fn_return( Payment_Intent::class, 'update', $payment_intent );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$this->issue_payment_intent_to_browser( 'pi_same' );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_same' ] ],
			$order
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'pi_same', $result['id'] );
	}

	/**
	 * Rejecting a bound PaymentIntent must forget the intent cookie so the next checkout load mints a
	 * fresh one, otherwise a buyer whose first order went terminal (e.g. a declined card, later Denied)
	 * is locked out of retrying: the same intent is served and rejected on every submit.
	 *
	 * @test
	 */
	public function should_forget_intent_cookie_when_rejecting_bound_payment_intent(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$first_order = $this->make_order_for_price( 100.0 );
		$this->bind_payment_intent_to_order( 'pi_bound', $first_order->ID );

		$retry_order = $this->make_order_for_price( 100.0 );

		$payment_intent = [
			'id'                     => 'pi_bound',
			'amount'                 => '10000',
			'application_fee_amount' => '0',
			'currency'               => 'usd',
			'status'                 => 'requires_payment_method',
			'metadata'               => $this->mint_cart_binding_metadata(),
			'client_secret'          => 'pi_bound_secret',
		];

		$this->set_class_fn_return( Payment_Intent::class, 'get', $payment_intent );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$forgot_intent = false;
		$this->set_class_fn_return(
			Payment_Intent_Handler::class,
			'store_payment_intent_cookie',
			function ( $payment_intent_id ) use ( &$forgot_intent ) {
				$forgot_intent = null === $payment_intent_id;

				return true;
			},
			true
		);

		$this->issue_payment_intent_to_browser( 'pi_bound' );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_bound' ] ],
			$retry_order
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'tec-tc-gateway-stripe-payment-intent-already-bound', $result->get_error_code() );
		$this->assertTrue( $forgot_intent, 'Rejecting a bound intent must clear the intent cookie.' );
	}

	/**
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function payment_intent_status_provider(): array {
		return [
			'requires_payment_method' => [ 'requires_payment_method', true ],
			'requires_confirmation'   => [ 'requires_confirmation', true ],
			'requires_action'         => [ 'requires_action', true ],
			'requires_source'         => [ 'requires_source', true ],
			'requires_source_action'  => [ 'requires_source_action', true ],
			'processing'              => [ 'processing', false ],
			'requires_capture'        => [ 'requires_capture', false ],
			'succeeded'               => [ 'succeeded', false ],
			'canceled'                => [ 'canceled', false ],
			'missing'                 => [ '', false ],
		];
	}

	/**
	 * An intent that has been paid or canceled must never be reused by another checkout.
	 *
	 * @test
	 * @dataProvider payment_intent_status_provider
	 */
	public function should_only_accept_payment_intents_that_can_still_take_a_payment( string $status, bool $expected ): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$payment_intent = $this->make_payment_intent( 'pi_status', [ 'status' => $status ] );

		$this->assertSame( $expected, Payment_Intent::is_valid_for_cart( $payment_intent, tribe( Cart::class ) ) );
	}

	/**
	 * @test
	 */
	public function should_reject_payment_intent_in_another_currency(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$this->assertFalse(
			Payment_Intent::is_valid_for_cart( $this->make_payment_intent( 'pi_eur', [ 'currency' => 'eur' ] ), tribe( Cart::class ) ),
			'An intent for the same amount in another currency must not be reused.'
		);
		$this->assertTrue(
			Payment_Intent::is_valid_for_cart( $this->make_payment_intent( 'pi_usd', [ 'currency' => 'USD' ] ), tribe( Cart::class ) ),
			'The currency comparison must not depend on case.'
		);
	}

	/**
	 * Checkout must not serve the browser an intent it remembers once that intent has been paid.
	 *
	 * @test
	 */
	public function should_mint_a_new_payment_intent_when_the_remembered_one_is_paid(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$this->make_order_for_price( 100.0 );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		$this->set_class_fn_return( Payment_Intent::class, 'get', $this->make_payment_intent( 'pi_paid', [ 'status' => 'succeeded' ] ) );
		$this->set_class_fn_return( Payment_Intent::class, 'create_from_cart', $this->make_payment_intent( 'pi_fresh' ) );
		$this->issue_payment_intent_to_browser( 'pi_paid' );

		$handler = new Payment_Intent_Handler();
		$handler->create_payment_intent_for_cart();

		$this->assertSame( 'pi_fresh', $handler->get()['id'] );
	}

	/**
	 * An intent ID learned elsewhere must not be bound, nor its client secret handed back.
	 *
	 * @test
	 */
	public function should_reject_create_with_payment_intent_not_issued_to_this_browser(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$order = $this->make_order_for_price( 100.0 );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		$this->issue_payment_intent_to_browser( 'pi_mine' );

		$stripe_was_called = false;
		$stub              = function () use ( &$stripe_was_called ) {
			$stripe_was_called = true;

			return $this->make_payment_intent( 'pi_someone_elses' );
		};
		$this->set_class_fn_return( Payment_Intent::class, 'get', $stub, true );
		$this->set_class_fn_return( Payment_Intent::class, 'update', $stub, true );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_someone_elses' ] ],
			$order
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'tec-tc-gateway-stripe-payment-intent-invalid', $result->get_error_code() );
		$this->assertFalse( $stripe_was_called, 'An intent not issued to this browser must not be fetched or updated.' );
	}

	/**
	 * @test
	 */
	public function should_reject_create_when_no_payment_intent_was_issued_to_this_browser(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$order = $this->make_order_for_price( 100.0 );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		$this->set_class_fn_return( Payment_Intent::class, 'get', $this->make_payment_intent( 'pi_unbound' ) );
		$this->set_class_fn_return( Payment_Intent::class, 'update', $this->make_payment_intent( 'pi_unbound' ) );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_unbound' ] ],
			$order
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'tec-tc-gateway-stripe-payment-intent-invalid', $result->get_error_code() );
	}

	/**
	 * Stripe IDs are mixed case, and the cookie is read back through sanitize_key().
	 *
	 * @test
	 */
	public function should_accept_create_with_mixed_case_payment_intent_issued_to_this_browser(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$order = $this->make_order_for_price( 100.0 );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		$payment_intent = $this->make_payment_intent( 'pi_3MtwBwLkdIwHu7ix' );
		$this->issue_payment_intent_to_browser( 'pi_3MtwBwLkdIwHu7ix' );
		$this->set_class_fn_return( Payment_Intent::class, 'get', $payment_intent );
		$this->set_class_fn_return( Payment_Intent::class, 'update', $payment_intent );

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_3MtwBwLkdIwHu7ix' ] ],
			$order
		);

		$this->assertNotInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'pi_3MtwBwLkdIwHu7ix', $result['id'] );
	}

	/**
	 * The intent ID and the intent cookie both come from the caller, so a third party who learned a
	 * victim's intent ID can point their own cookie at it. The intent must also carry the cart it was
	 * created for.
	 *
	 * @test
	 */
	public function should_reject_create_with_payment_intent_created_for_another_cart_with_the_same_total(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$order = $this->make_order_for_price( 100.0 );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );

		$this->set_class_fn_return( Cart::class, 'get_cart_hash', 'victimcart01' );
		$victims_intent = $this->make_payment_intent( 'pi_victim' );

		$this->set_class_fn_return( Cart::class, 'get_cart_hash', 'attackcart01' );
		$this->issue_payment_intent_to_browser( 'pi_victim' );

		$stripe_was_mutated = false;
		$this->set_class_fn_return( Payment_Intent::class, 'get', $victims_intent );
		$this->set_class_fn_return(
			Payment_Intent::class,
			'update',
			function () use ( &$stripe_was_mutated, $victims_intent ) {
				$stripe_was_mutated = true;

				return $victims_intent;
			},
			true
		);

		$result = tribe( Payment_Intent_Handler::class )->update_payment_intent(
			[ 'payment_intent' => [ 'id' => 'pi_victim' ] ],
			$order
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertEquals( 'tec-tc-gateway-stripe-payment-intent-cart-mismatch', $result->get_error_code() );
		$this->assertFalse( $stripe_was_mutated, 'An intent created for another cart must not be updated at Stripe.' );
	}

	/**
	 * @test
	 */
	public function should_reject_payment_intent_without_a_cart_binding(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$this->assertFalse(
			Payment_Intent::is_valid_for_cart( $this->make_payment_intent( 'pi_unbound', [ 'metadata' => [] ] ), tribe( Cart::class ) )
		);
	}

	/**
	 * The coupon routes mutate an intent by its caller-supplied ID, so is_bound_to_cart() is what
	 * keeps a known ID from pointing those routes at another buyer's intent.
	 *
	 * @test
	 */
	public function should_only_consider_an_intent_bound_to_the_cart_that_created_it(): void {
		$this->set_class_fn_return( Settings::class, 'is_licensed_plugin', true );
		$this->set_class_fn_return( Cart::class, 'get_cart_total', 100.0 );
		$this->set_class_fn_return( Cart::class, 'get_cart_hash', 'buyercart001' );
		tribe_update_option( Currency::$currency_code_option, 'USD' );

		$bound = $this->make_payment_intent( 'pi_bound' );

		$this->assertTrue(
			Payment_Intent::is_bound_to_cart( $bound, tribe( Cart::class ) ),
			'An intent created for this cart must be recognised as bound.'
		);
		$this->assertFalse(
			Payment_Intent::is_bound_to_cart( $this->make_payment_intent( 'pi_none', [ 'metadata' => [] ] ), tribe( Cart::class ) ),
			'An intent carrying no binding must not be treated as bound.'
		);

		// The same intent, judged against a different cart, must no longer match.
		$this->set_class_fn_return( Cart::class, 'get_cart_hash', 'othercart002' );
		$this->assertFalse(
			Payment_Intent::is_bound_to_cart( $bound, tribe( Cart::class ) ),
			'An intent created for another cart must not be treated as bound.'
		);
	}

	/**
	 * Builds a $100.00 USD intent shaped like Stripe's response.
	 *
	 * @param string                $id        The intent ID.
	 * @param array<string,string>  $overrides Fields to replace.
	 *
	 * @return array{id:string,amount:string,application_fee_amount:string,currency:string,status:string,metadata:array<string,string>,client_secret:string}
	 */
	protected function make_payment_intent( string $id, array $overrides = [] ): array {
		return array_merge(
			[
				'id'                     => $id,
				'amount'                 => '10000',
				'application_fee_amount' => '0',
				'currency'               => 'usd',
				'status'                 => 'requires_payment_method',
				'metadata'               => $this->mint_cart_binding_metadata(),
				'client_secret'          => $id . '_secret',
			],
			$overrides
		);
	}

	/**
	 * Sets the intent cookie the checkout page leaves in the buyer's browser.
	 *
	 * @param string $payment_intent_id The intent ID checkout issued.
	 *
	 * @return void
	 */
	protected function issue_payment_intent_to_browser( string $payment_intent_id ): void {
		$_COOKIE[ tribe( Payment_Intent_Handler::class )->get_payment_intent_cookie_name() ] = $payment_intent_id;
	}

	/**
	 * Store a gateway order id on an order the way the create endpoint does.
	 *
	 * @param string $payment_intent_id The Stripe PaymentIntent id to bind.
	 * @param int    $order_id          The order to bind it to.
	 *
	 * @return void
	 */
	protected function bind_payment_intent_to_order( string $payment_intent_id, int $order_id ): void {
		tec_tc_orders()
			->by_args( [ 'id' => $order_id ] )
			->set_args( [ 'gateway_order_id' => $payment_intent_id ] )
			->save();

		clean_post_cache( $order_id );
	}
}
