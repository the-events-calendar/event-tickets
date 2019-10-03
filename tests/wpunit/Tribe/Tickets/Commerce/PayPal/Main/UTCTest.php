<?php

namespace Tribe\Tickets\Commerce\PayPal\Main;

use Tribe\Tickets\Test\Testcases\Ticket_Object_TestCase;

class UTCTest extends Ticket_Object_TestCase {
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
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1, $overrides );

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
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1, $overrides );

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
		add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1, $overrides );

		$this->create_many_attendees_for_ticket( 15, $paypal_ticket_id, $post_id );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );

		$first_attendee = current( $paypal_attendees );

		$first_attendee['order_status'] = \Tribe__Tickets__Commerce__PayPal__Stati::$pending;

		tribe_update_option( 'ticket-paypal-stock-handling', 'on-pending' );

		self::assertTrue( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}

	/**
	 * It should allow decreasing inventory for newer pending attendee.
	 *
	 * @test
	 */
	public function it_should_allow_decreasing_inventory_for_newer_pending_attendee() {
		add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-10 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+10 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1, $overrides );

		$this->create_many_attendees_for_ticket( 15, $paypal_ticket_id, $post_id );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );

		$date = new \DateTime( '-29 minutes', new \DateTimeZone( $this->timezone ) );

		foreach ( $paypal_attendees as $k => $attendee ) {
			$paypal_attendees[ $k ]['purchase_time'] = $date->format( \Tribe__Date_Utils::DBDATETIMEFORMAT );
		}

		$first_attendee = current( $paypal_attendees );

		$first_attendee['order_status'] = \Tribe__Tickets__Commerce__PayPal__Stati::$pending;

		tribe_update_option( 'ticket-paypal-stock-handling', 'on-pending' );

		self::assertTrue( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}

	/**
	 * It should allow decreasing inventory for older pending attendee.
	 *
	 * @test
	 */
	public function it_should_allow_decreasing_inventory_for_older_pending_attendee() {
		add_filter( 'tribe_tickets_tpp_pending_stock_ignore', '__return_false' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal_provider */
		$paypal_provider = tribe( 'tickets.commerce.paypal' );

		$post_id  = $this->make_event();

		$overrides = [
			'meta_input' => [
				'_ticket_start_date' => date( 'Y-m-d H:i:s', strtotime( '-50 minutes' ) ),
				'_ticket_end_date'   => date( 'Y-m-d H:i:s', strtotime( '+50 minutes' ) ),
			],
		];

		$paypal_ticket_id = $this->create_paypal_ticket( $post_id, 1, $overrides );

		$this->create_many_attendees_for_ticket( 15, $paypal_ticket_id, $post_id );

		$paypal_attendees = $paypal_provider->get_attendees_by_id( $post_id );

		$date = new \DateTime( '-31 minutes', new \DateTimeZone( $this->timezone ) );

		foreach ( $paypal_attendees as $k => $attendee ) {
			$paypal_attendees[ $k ]['purchase_time'] = $date->format( \Tribe__Date_Utils::DBDATETIMEFORMAT );
		}

		$first_attendee = current( $paypal_attendees );

		$first_attendee['order_status'] = \Tribe__Tickets__Commerce__PayPal__Stati::$pending;

		tribe_update_option( 'ticket-paypal-stock-handling', 'on-pending' );

		self::assertFalse( $paypal_provider->attendee_decreases_inventory( $first_attendee ) );
	}
}
