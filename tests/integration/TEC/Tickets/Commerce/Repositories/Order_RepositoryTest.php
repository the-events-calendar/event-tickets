<?php

namespace TEC\Tickets\Commerce\Repositories;

use TEC\Tickets\Commerce\Gateways\Free\Gateway as FreeGateway;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway as PayPalGateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway as StripeGateway;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Order_RepositoryTest extends \Codeception\TestCase\WPTestCase {

	use Order_Maker;
	use Ticket_Maker;

	/**
	 * @test
	 */
	public function it_should_get_distinct_values_of_key() {
		$order_repository = tribe( Order_Repository::class );

		$this->assertEmpty( $order_repository->get_distinct_values_of_key( 'gateway' ) );
		$this->assertEmpty( $order_repository->get_distinct_values_of_key( 'purchaser_email' ) );
		$this->assertEmpty( $order_repository->get_distinct_values_of_key( 'purchaser_first_name' ) );
		$this->assertEmpty( $order_repository->get_distinct_values_of_key( 'not_exists' ) );

		$this->prepare_test_data();

		$this->assertEquals( [ 'free', 'stripe', 'paypal' ], $order_repository->get_distinct_values_of_key( 'gateway' ) );

		$this->assertEmpty( $order_repository->get_distinct_values_of_key( 'not_exists' ) );

		$results = array_map( function ( $v ) {
			return 'test-' . $v . '@test.com';
		}, range( 1, 8 ) );

		$this->assertEquals( [ 'Test' ], $order_repository->get_distinct_values_of_key( 'purchaser_first_name' ) );
		$this->assertEquals( $results, $order_repository->get_distinct_values_of_key( 'purchaser_email' ) );
	}

	/**
	 * Prepare test data.
	 *
	 * @return array
	 */
	protected function prepare_test_data() {
		$event_ids  = $this->create_test_events();
		$tickets    = $this->create_test_tickets( $event_ids );
		$orders     = $this->create_test_orders( $tickets );

		return [ $orders, $tickets, $event_ids ];
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
	protected function create_test_tickets( $event_ids, array $number_of_tickets_per_event = [ 1, 1, 2 ] ) {
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
	protected function create_test_orders( $tickets, $number_of_orders_per_ticket = 2 ) {
		$orders = [];

		$counter = 1;

		foreach ( $tickets as $ticket ) {
			for ( $i = 0; $i < $number_of_orders_per_ticket; $i ++ ) {
				$overrides = [
					'purchaser_user_id'    => $counter,
					'purchaser_full_name'  => 'Test Purchaser ' . $counter,
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser ' . $counter,
					'purchaser_email'      => 'test-' . $counter . '@test.com',
				];

				if ( $counter < 3 ) {
					$gateway = tribe( FreeGateway::class );
				} elseif ( $counter > 6 ) {
					$gateway = tribe( PayPalGateway::class );
				} else {
					$gateway = tribe( StripeGateway::class );
				}

				$overrides['gateway'] = $gateway;

				$orders[] = $this->create_order( [ $ticket => 1 ], $overrides );
				$counter++;
			}
		}

		return $orders;
	}
}
