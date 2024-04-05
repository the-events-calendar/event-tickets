<?php


namespace TEC\Tickets\Commerce\Gateways\Stripe;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Order as Commerce_Order;
use TEC\Tickets\Commerce\Status\Status_Handler;

class OrderTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Attendee_Maker;
	use Order_Maker;
	use With_Uopz;

	/**
	 * Data provider for testing different scenarios of get_gateway_dashboard_url_by_order.
	 *
	 * @return array
	 */
	public function gatewayPayloadProvider() {
		$fake_gateway_order_id = '2MJ687450D400282F';
		$base_order_object     = $this->create_order_with_ticket_and_attendee( $fake_gateway_order_id );
		$status                = tribe( Status_Handler::class )->get_by_wp_slug( $base_order_object->post_status );
		$status_slug           = $status::SLUG;

		// Proper ID and livemode = true
		$order_object                  = clone $base_order_object;
		$order_object->gateway_payload = [
			$status_slug => [
				[
					'id'       => $fake_gateway_order_id,
					'livemode' => true,
				],
			],
		];
		yield 'proper id and livemode = true' => [
			$order_object,
			'https://dashboard.stripe.com/payments/' . $fake_gateway_order_id,
		];

		// Proper ID and livemode = false
		$order_object                  = clone $base_order_object;
		$order_object->gateway_payload = [
			$status_slug => [
				[
					'id'       => $fake_gateway_order_id,
					'livemode' => false,
				],
			],
		];
		yield 'proper id and livemode = false' => [
			$order_object,
			'https://dashboard.stripe.com/test/payments/' . $fake_gateway_order_id,
		];

		// Improper ID and livemode = true
		$order_object                  = clone $base_order_object;
		$order_object->gateway_payload = [
			$status_slug => [
				[
					'id'       => 'improper id',
					'livemode' => true,
				],
			],
		];
		yield 'improper id and livemode = true' => [
			$order_object,
			'https://dashboard.stripe.com/payments/improper id',
		];

		// Improper ID and livemode = false
		$order_object                  = clone $base_order_object;
		$order_object->gateway_payload = [
			$status_slug => [
				[
					'id'       => null,
					'livemode' => false,
				],
			],
		];
		yield 'improper id and livemode = false' => [
			$order_object,
			'https://dashboard.stripe.com/test/payments/',
		];

		// null gateway_payload
		$order_object                  = clone $base_order_object;
		$order_object->gateway_payload = [];
		yield 'empty gateway_payload' => [
			$order_object,
			null,
		];
	}

	/**
	 * @test
	 * @dataProvider gatewayPayloadProvider
	 */
	public function it_should_get_order_url( $order, $expected_url ) {
		$order_object     = $order;
		$stripe_order_url = tribe( \TEC\Tickets\Commerce\Gateways\Stripe\Order::class )->get_gateway_dashboard_url_by_order( $order_object );

		$this->assertEquals( $expected_url, $stripe_order_url, 'The returned Stripe dashboard URL does not match the expected value for the provided dataset.' );
	}

	/**
	 * Helper method to create an order for a post.
	 *
	 * @since TBD
	 *
	 * @param string $gateway_order_id
	 *
	 * @return \WP_Post
	 */
	public function create_order_with_ticket_and_attendee( $gateway_order_id ) {
		// Create a post and a ticket for it.
		$post_id                    = $this->factory()->post->create();
		$tickets_commerce_ticket_id = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'ticket_name'        => 'Test TC ticket',
				'ticket_description' => 'Test TC ticket description',
			]
		);

		// Create an order for one ticket.
		$order = $this->create_order(
			[ $tickets_commerce_ticket_id => 1 ],
			[ 'purchaser_email' => 'purchaser_email@test.com' ],
		);

		// Update order info for normalization.
		update_post_meta( $order->ID, Commerce_Order::$gateway_order_id_meta_key, $gateway_order_id );
		$fake_date = '1974-11-20 21:57:56';
		wp_update_post(
			[
				'ID'                => $order->ID,
				'post_date'         => $fake_date,
				'post_date_gmt'     => $fake_date,
				'post_modified'     => $fake_date,
				'post_modified_gmt' => $fake_date,
			]
		);

		$attendee_id = $this->create_attendee_for_ticket(
			$tickets_commerce_ticket_id,
			$post_id,
			[ 'order_id' => $order->ID ]
		);

		return $order;
	}

}