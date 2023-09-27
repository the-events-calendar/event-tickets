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
		$event_1_id= $maker->create();
		$event_2_id = $maker->create();

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 30,
			],
		];
		$event_1_ticket   = $this->create_tc_ticket( $event_1_id, 10, $overrides );

		$overrides = [
			'tribe-ticket' => [
				'mode'           => \Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE,
				'event_capacity' => 50,
				'capacity'       => 50,
			],
		];
		$event_2_ticket   = $this->create_tc_ticket( $event_2_id, 20, $overrides );

		$attendees_for_event_1 = $this->create_many_attendees_for_ticket( 5, $event_1_ticket, $event_1_id );


		codecept_debug($attendees_for_event_1);
		/**
		 * Our goal is to move a single ticket from Event 0 to Event 1.
		 */

		/**
		 * action    "move_tickets"
		 * src_post_id    "14067" Event 1
		 * target_post_id    "14070" Event 2
		 * check    "227674bfdc"
		 * ticket_ids[]    "14082" ID of Attendee
		 * target_type_id    "14071" Event 2 Ticket
		 */

		// Now that our scenario is set up, lets move ticket_a to the second event.
		$successful_moves = tribe( 'Tribe__Tickets__Admin__Move_Tickets' )->move_tickets( [ $attendees_for_event_1 ],
		                                                                                  $event_2_ticket,
		                                                                                  $event_1_id,
		                                                                                  $event_2_id );
		codecept_debug("Moved ". $successful_moves . " tickets");


	}

}