<?php

namespace Tribe\Tickets\Test\Traits;

use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

trait With_Test_Orders {
	use Order_Maker;
	use Ticket_Maker;

	/**
	 * Created orders.
	 *
	 * @var array
	 */
	protected $orders;

	/**
	 * Created tickets.
	 *
	 * @var array
	 */
	protected $tickets;

	/**
	 * Created event IDs.
	 *
	 * @var array
	 */
	protected $event_ids;

	/**
	 * Prepare test data.
	 *
	 * @return array
	 */
	protected function prepare_test_data( $with_wp_users = false ) {
		if ( ! empty( $this->orders ) ) {
			return [ $this->orders, $this->tickets, $this->event_ids ];
		}

		$this->event_ids  = $this->create_test_events();
		$this->tickets    = $this->create_test_tickets( $this->event_ids );
		$this->orders     = $this->create_test_orders( $this->tickets, 2 , $with_wp_users );

		return [ $this->orders, $this->tickets, $this->event_ids ];
	}

	/**
	 * Create test events.
	 *
	 * @param int $number_of_events
	 *
	 * @return array
	 */
	protected function create_test_events( $number_of_events = 3 ) {
		$events_ids = [];

		for ( $i = 0; $i < $number_of_events; $i ++ ) {
			$event_ts = strtotime( '2025-01-01 00:00:00' ) + $i * DAY_IN_SECONDS;
			$event_dt = new \DateTime( "@$event_ts" );

			$events_ids[] = tribe_events()->set_args(
				[
					'title'      => 'Event ' . ( $i + 1 ),
					'status'     => 'publish',
					'start_date' => $event_dt->format( 'Y-m-d H:i:s' ),
					'duration'   => ( $i + 1 ) * HOUR_IN_SECONDS,
				]
			)->create()->ID;
		}

		return $events_ids;
	}

	/**
	 * Create test tickets.
	 *
	 * @param array $event_ids
	 * @param array $number_of_tickets_per_event
	 *
	 * @return array
	 */
	protected function create_test_tickets( $event_ids, array $number_of_tickets_per_event = [ 1, 0, 2 ] ) {
		$ticket_ids = [];

		foreach ( $event_ids as $key => $event_id ) {
			for ( $i = 0; $i < $number_of_tickets_per_event[ $key ]; $i ++ ) {
				$ticket_ids[] = $this->create_tc_ticket( $event_id );
			}
		}

		return $ticket_ids;
	}

	/**
	 * Create test orders.
	 *
	 * @param array $tickets
	 * @param int   $number_of_orders_per_ticket
	 *
	 * @return array
	 */
	protected function create_test_orders( $tickets, $number_of_orders_per_ticket = 2, $with_wp_users = false ) {
		$orders = [];

		$counter = 1;

		foreach ( $tickets as $ticket ) {
			for ( $i = 0; $i < $number_of_orders_per_ticket; $i ++ ) {
				if ( $with_wp_users ) {
					$user_id = wp_insert_user( [
						'user_pass'    => 'TEST_PASS_' . $counter,
						'user_login'   => 'test_user_' . $counter,
						'user_email'   => 'test-' . $counter . '@test.com',
						'display_name' => 'Test Purchaser ' . $counter,
						'first_name'   => 'Test',
						'last_name'    => 'Purchaser ' . $counter,
						'role'         => 'contributor',
					] );
				}

				$default_purchaser = [
					'purchaser_user_id'    => ! $with_wp_users || is_wp_error( $user_id ) ? $counter : $user_id,
					'purchaser_full_name'  => 'Test Purchaser ' . $counter,
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser ' . $counter,
					'purchaser_email'      => 'test-' . $counter . '@test.com',
				];

				if ( $with_wp_users && ! is_wp_error( $user_id ) ) {
					$this->user_ids[ $counter ] = $user_id;
				}

				$order = $this->create_order( [ $ticket => 1 ], $default_purchaser );
				wp_update_post( [ 'ID' => $order->ID, 'post_date' => '2024-05-12 12:30:45', 'post_date_gmt' => '2024-05-12 12:30:45' ] );
				$orders[] = tec_tc_get_order( $order->ID );
				$counter++;
			}
		}

		return $orders;
	}
}
