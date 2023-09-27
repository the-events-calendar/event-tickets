<?php

namespace TEC\Admin;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Provider;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe__Tickets__Tickets;

class MoveTicketsTest extends \Codeception\TestCase\WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @inheritDoc
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();

		tribe_register_provider( Provider::class );

		add_filter( 'tribe_tickets_ticket_object_is_ticket_cache_enabled', '__return_false' );

		//Set our default provider to TC so the logic below works properly.
		add_filter( 'tribe_tickets_get_default_module', function ( $default_module, $modules ) {
			return 'TEC\Tickets\Commerce\Module';
		},          10, 2 );

	}

	public function test_tc_shared_capacity_purchase() {
		$maker = new Event();


		/**
		 * Setup our test structure, Create two events, one event with 2 tickets, another with 1 ticket.
		 */

		$events = [];

		$events[0]['event_id'] = $maker->create();

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];

		$events[0]['tickets'] = [
			$this->create_tc_ticket( $events[0]['event_id'], 10, $overrides ),
			$this->create_tc_ticket( $events[0]['event_id'], 20, $overrides )
		];

		$events[1]['event_id'] = $maker->create();

		$events[1]['tickets'] = [
			$this->create_tc_ticket( $events[1]['event_id'], 10, $overrides ),
			$this->create_tc_ticket( $events[1]['event_id'], 20, $overrides )
		];


		$test = $this->create_attendee_for_ticket(  $events[0]['tickets'][0], $events[0]['event_id'] );


		codecept_debug($events);
		/**
		 * Our goal is to move a single ticket from Event 0 to Event 1.
		 */

		// Now that our scenario is set up, lets move ticket_a to the second event.
		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets( [ $events[0]['tickets'][0] ],
		                                                                                  $events[1]['tickets'][0],
		                                                                                  $events[0]['event_id'],
		                                                                                  $events[1]['event_id'] );
		codecept_debug("Moved ". $successful_moves . " tickets");


	}

}