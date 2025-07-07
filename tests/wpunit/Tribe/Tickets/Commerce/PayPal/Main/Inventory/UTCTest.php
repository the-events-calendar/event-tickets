<?php

namespace Tribe\Tickets\Commerce\PayPal\Main\Inventory;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;

class UTCTest extends Ticket_Object_TestCase {
	use PayPal_Order_Maker;
	
	protected $timezone = 'UTC';

	/**
	 * It should not allow decreasing inventory for non-complete attendee.
	 *
	 * @test
	 */
	public function it_should_not_allow_decreasing_inventory_for_non_complete_attendee() {
		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );

		$this->create_many_attendees_for_ticket( 15, $paypal_ticket_id, $post_id );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );

		$first_attendee = current( $paypal_attendees );

		// Just manually set something here to prove logic.
		$first_attendee['order_status'] = 'non_complete_status';

		self::assertFalse( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}

	/**
	 * It should allow decreasing inventory for attendee.
	 *
	 * @test
	 */
	public function it_should_allow_decreasing_inventory_for_attendee() {
		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );

		$this->create_many_attendees_for_ticket( 15, $paypal_ticket_id, $post_id );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );

		foreach ( $paypal_attendees as $k => $attendee ) {
			wp_update_post( [
				'ID'        => $attendee['order_id'],
				'post_date' => date( 'Y-m-d H:i:s', strtotime( '-31 minutes' ) ),
				'edit_date' => true,
			] );
		}

		$first_attendee = current( $paypal_attendees );

		self::assertTrue( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}

	/**
	 * It should not allow decreasing inventory for pending attendee.
	 *
	 * @test
	 */
	public function it_should_not_allow_decreasing_inventory_for_pending_attendee() {
		add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false', 999 );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );

		// Set stock handling to decrease on pending orders
		tribe_update_option( 'ticket-paypal-stock-handling', 'on-pending' );

		// Create proper PayPal orders with order posts using 'pending-payment' status
		$generated_orders = $this->create_paypal_orders(
			$post_id,
			$paypal_ticket_id,
			1, // 1 ticket per order
			1, // 1 order
			\Tribe__Tickets__Commerce__PayPal__Stati::$pending
		);

		// Get the transaction ID and find the WordPress order post ID
		$transaction_id = $generated_orders[0]['Order ID'];
		$order_post_id = \Tribe__Tickets__Commerce__PayPal__Order::find_by_order_id( $transaction_id );

		// Update the order post creation date to be older than 30 minutes (expired reservation)
		wp_update_post( [
			'ID'        => $order_post_id,
			'post_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-35 minutes' ) ),
			'edit_date' => true,
		] );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );
		$first_attendee = current( $paypal_attendees );

		// Should NOT allow decreasing inventory because reservation has expired (35 minutes > 30 minutes)
		self::assertFalse( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}

	/**
	 * It should allow decreasing inventory for newer pending attendee.
	 *
	 * @test
	 */
	public function it_should_allow_decreasing_inventory_for_newer_pending_attendee() {
		add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false', 999 );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );

		// Set stock handling to decrease on pending orders
		tribe_update_option( 'ticket-paypal-stock-handling', 'on-pending' );

		// Create proper PayPal orders with order posts using 'pending-payment' status
		$generated_orders = $this->create_paypal_orders(
			$post_id,
			$paypal_ticket_id,
			1, // 1 ticket per order
			1, // 1 order
			\Tribe__Tickets__Commerce__PayPal__Stati::$pending
		);

		// Get the transaction ID and find the WordPress order post ID
		$transaction_id = $generated_orders[0]['Order ID'];
		$order_post_id = \Tribe__Tickets__Commerce__PayPal__Order::find_by_order_id( $transaction_id );

		// Update the order post creation date to be within 30 minutes (valid reservation)
		wp_update_post( [
			'ID'        => $order_post_id,
			'post_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-29 minutes' ) ),
			'edit_date' => true,
		] );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );
		$first_attendee = current( $paypal_attendees );

		// Should allow decreasing inventory because reservation is still valid (29 minutes < 30 minutes)
		self::assertTrue( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}

	/**
	 * It should not allow decreasing inventory for older pending attendee.
	 *
	 * @test
	 */
	public function it_should_not_allow_decreasing_inventory_for_older_pending_attendee() {
		add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false', 999 );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-50 minutes' ) ),
				'_ticket_end_date'   => $this->get_local_datetime_string_from_utc_time( strtotime( '+50 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket_basic( $post_id, 1, $overrides );

		// Set stock handling to decrease on pending orders
		tribe_update_option( 'ticket-paypal-stock-handling', 'on-pending' );

		// Create proper PayPal orders with order posts using 'pending-payment' status
		$generated_orders = $this->create_paypal_orders(
			$post_id,
			$paypal_ticket_id,
			1, // 1 ticket per order
			1, // 1 order
			\Tribe__Tickets__Commerce__PayPal__Stati::$pending
		);

		// Get the transaction ID and find the WordPress order post ID
		$transaction_id = $generated_orders[0]['Order ID'];
		$order_post_id = \Tribe__Tickets__Commerce__PayPal__Order::find_by_order_id( $transaction_id );

		// Update the order post creation date to be older than 30 minutes (expired reservation)
		wp_update_post( [
			'ID'        => $order_post_id,
			'post_date' => $this->get_local_datetime_string_from_utc_time( strtotime( '-31 minutes' ) ),
			'edit_date' => true,
		] );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );
		$first_attendee = current( $paypal_attendees );

		// Should NOT allow decreasing inventory because reservation has expired (31 minutes > 30 minutes)
		self::assertFalse( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}
}
