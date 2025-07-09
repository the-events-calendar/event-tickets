<?php

namespace TEC\Tickets\Commerce\Stock;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Events\Test\Factories\Event;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Metabox as Metabox;

class UpdateTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;
	use RSVP_Ticket_Maker;
	use With_Uopz;

	public function test_ticket_restock_after_attendee_deletion_with_individual_capacity() {
		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 100, $ticket->available(), 'There should be 100 tickets available' );

		// create order.
		$order = $this->create_order( [ $ticket_a_id => 2 ] );

		//get attendees.
		$attendees = tribe_attendees()->where( 'event_id', $event_id )->all();

		$this->assertEquals( 2, count( $attendees ), 'There should be 2 attendees' );

		// delete attendee.
		$attendee = $attendees[0];
		$deleted  = tribe( Attendee::class )->delete( $attendee->ID );

		$new_count = tec_tc_attendees()->by( 'event_id', $event_id )->count();
		$this->assertEquals( 1, $new_count, 'There should be 1 attendee' );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 99, $ticket->available(), 'There should be 99 tickets available' );
	}

	public function test_attendee_deletion_permission_check() {
		$maker    = new Event();
		$event_id = $maker->create();

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );
		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );

		// create order.
		$order = $this->create_order( [ $ticket_a_id => 2 ] );

		//get attendees.
		$attendees = tribe_attendees()->where( 'event_id', $event_id )->all();

		$this->assertEquals( 2, count( $attendees ), 'There should be 2 attendees' );

		$contributor_user = $this->factory()->user->create( [ 'role' => 'contributor' ] );

		wp_set_current_user( $contributor_user );

		$attendee = $attendees[0];

		$_POST['post_id'] = $event_id;
		$_POST['ticket_id'] = $attendee->ID;
		$_POST['nonce'] = wp_create_nonce( 'remove_ticket_nonce' );

		$error_called = 0;
		$success_called = 0;

		$this->set_fn_return( 'wp_send_json_error', function () use ( &$error_called ) {
			$error_called++;
			return true;
		}, true );
		$this->set_fn_return( 'wp_send_json_success', function () use ( &$success_called ) {
			$success_called++;
			return true;
		}, true );

		tribe( Metabox::class )->ajax_ticket_delete();

		$this->assertEquals( 1, $error_called, 'Contributor should not be able to delete attendee' );
		$this->assertEquals( 0, $success_called, 'Contributor should not be able to delete attendee' );

		// Become administrator.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		// Fresh nonce.
		$_POST['nonce'] = wp_create_nonce( 'remove_ticket_nonce' );

		tribe( Metabox::class )->ajax_ticket_delete();

		$this->assertEquals( 1, $error_called, 'Contributor should not be able to delete attendee' );
		$this->assertEquals( 1, $success_called, 'Admin should be able to delete attendee' );
	}

	/**
	 * @after
	 */
	public function request_cleaner() {
		unset(
			$_POST['post_id'],
			$_POST['ticket_id'],
			$_POST['nonce'],
		);
	}
}
