<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;

use TEC\Tickets\Commerce\Gateways\Stripe\Merchant;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Refunded;
use TEC\Tickets\Commerce\Status\Status_Handler;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;
use WP_REST_Response;

class Charge_Webhook_Test extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;
	use With_Uopz;

	/**
	 * @before
	 */
	public function ensure_clean_cart() {
		tribe( \TEC\Tickets\Commerce\Cart::class )->clear_cart();
	}

	/**
	 * The Stripe Merchant needs to be connected for orders to be able to transition all the way to Completed.
	 *
	 * @before
	 */
	public function connect_stripe_merchant() {
		tribe( Merchant::class )->save_signup_data(
			[
				'stripe_user_id' => 'STRIPE_USER_ID',
				'sandbox'        => (object) [
					'access_token' => 'STRIPE_SANDBOX_TOKEN',
				],
				'live'           => (object) [
					'access_token' => 'STRIPE_LIVE_TOKEN',
				],
			]
		);
	}

	/**
	 * @after
	 */
	public function cleanup_cart() {
		tribe( \TEC\Tickets\Commerce\Cart::class )->clear_cart();
	}

	/**
	 * @after
	 */
	public function disconnect_stripe_merchant() {
		tribe( Merchant::class )->save_signup_data( [] );
	}

	/**
	 * Builds a fake `charge.refunded` Stripe event payload for a given payment intent.
	 *
	 * @param string $payment_intent_id The payment intent ID the charge belongs to.
	 * @param array  $metadata          Metadata to attach to the charge object.
	 *
	 * @return array
	 */
	protected function build_charge_refunded_event( string $payment_intent_id, array $metadata = [] ): array {
		return [
			'id'   => 'evt_' . uniqid(),
			'type' => Events::CHARGE_REFUNDED,
			'data' => [
				'object' => [
					'payment_intent' => $payment_intent_id,
					'metadata'       => $metadata,
				],
			],
		];
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Charge_Webhook::handle
	 */
	public function it_should_refund_a_completed_order_on_charge_refunded_event() {
		$post      = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post, 10 );

		$order = $this->create_order_through_stripe( [ $ticket_id => 1 ] );

		// `$order` reflects its state before the status transitions were applied; re-fetch to get the current status.
		$order = tec_tc_get_order( $order->ID );

		$this->assertEquals( Completed::SLUG, str_replace( 'tec-tc-', '', $order->post_status ) );

		$payment_intent_id = 'pi_test_' . uniqid();

		tec_tc_orders()
			->by_args( [ 'id' => $order->ID, 'status' => $order->post_status ] )
			->set_args( [ 'gateway_order_id' => $payment_intent_id ] )
			->save();

		$this->set_class_fn_return(
			Payment_Intent::class,
			'get',
			[
				'id'     => $payment_intent_id,
				'status' => 'succeeded',
			]
		);

		$new_status = tribe( Status_Handler::class )->get_by_slug( Refunded::SLUG );

		$result = Charge_Webhook::handle(
			$this->build_charge_refunded_event( $payment_intent_id ),
			$new_status,
			new WP_REST_Request( 'POST', '/' ),
			new WP_REST_Response()
		);

		$this->assertTrue( (bool) $result );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEquals( $new_status->get_wp_slug(), $refreshed_order->post_status );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Charge_Webhook::handle
	 */
	public function it_should_defer_refund_when_order_is_on_checkout_screen_hold() {
		$post      = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$ticket_id = $this->create_tc_ticket( $post, 10 );

		$order = $this->create_order_through_stripe( [ $ticket_id => 1 ] );

		// `$order` reflects its state before the status transitions were applied; re-fetch to get the current status.
		$order = tec_tc_get_order( $order->ID );

		$payment_intent_id = 'pi_test_' . uniqid();

		tec_tc_orders()
			->by_args( [ 'id' => $order->ID, 'status' => $order->post_status ] )
			->set_args( [ 'gateway_order_id' => $payment_intent_id ] )
			->save();

		tribe( Order::class )->set_on_checkout_screen_hold( $order->ID );

		$this->set_class_fn_return(
			Payment_Intent::class,
			'get',
			[
				'id'     => $payment_intent_id,
				'status' => 'succeeded',
			]
		);

		$new_status = tribe( Status_Handler::class )->get_by_slug( Refunded::SLUG );

		$result = Charge_Webhook::handle(
			$this->build_charge_refunded_event( $payment_intent_id ),
			$new_status,
			new WP_REST_Request( 'POST', '/' ),
			new WP_REST_Response()
		);

		// The webhook is deferred, not applied immediately.
		$this->assertFalse( $result );

		$refreshed_order = tec_tc_get_order( $order->ID );

		$this->assertEquals( Completed::SLUG, str_replace( 'tec-tc-', '', $refreshed_order->post_status ) );

		$pending = tribe( Webhooks::class )->get_pending_webhooks( $order->ID );

		$this->assertNotEmpty( $pending );
		$this->assertEquals( $new_status->get_wp_slug(), $pending[0]['new_status'] );
	}

	/**
	 * @test
	 *
	 * @covers TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Charge_Webhook::handle
	 */
	public function it_should_error_when_payment_intent_does_not_correspond_to_a_known_order() {
		$payment_intent_id = 'pi_unknown_' . uniqid();

		$this->set_class_fn_return(
			Payment_Intent::class,
			'get',
			[
				'id'     => $payment_intent_id,
				'status' => 'succeeded',
			]
		);

		$new_status = tribe( Status_Handler::class )->get_by_slug( Refunded::SLUG );

		// Metadata flags this as a Tickets Commerce transaction, but no matching order exists.
		$event = $this->build_charge_refunded_event(
			$payment_intent_id,
			[ Payment_Intent::$tc_metadata_identifier => true ]
		);

		$result = Charge_Webhook::handle(
			$event,
			$new_status,
			new WP_REST_Request( 'POST', '/' ),
			new WP_REST_Response()
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
