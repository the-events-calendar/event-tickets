<?php

namespace Tribe\Tickets;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Module;

/**
 * Test Attendees_List class.
 *
 * @since TBD
 *
 * @covers \Tribe\Tickets\Events\Attendees_List
 */
class Attendees_ListTest extends \Codeception\TestCase\WPTestCase{
	use RSVP_Ticket_Maker;
	use Ticket_Maker;
	use Attendee_Maker;

	public function setUp(): void {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tickets Commerce as the default provider.
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[Module::class] = Module::class;
			return $modules;
		} );
	}

	/**
	 * @test
	 *
	 * @covers \Tribe\Tickets\Events\Attendees_List::get_attendance_counts
	 */
	public function test_get_attendance_count_for_rsvp_attendees() {
		$post_id = $this->factory()->post->create();

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendees = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$this->assertEquals( count($attendees), $attendees_list->get_attendance_counts( $post_id ) );
	}

	/**
	 * @test
	 *
	 * @covers \Tribe\Tickets\Events\Attendees_List::get_attendance_counts
	 */
	public function test_get_attendance_count_for_tickets_commerce_attendees() {
		$post_id = $this->factory()->post->create();

		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );
		$attendees = $this->create_many_attendees_for_ticket( 5, $tickets_commerce_ticket_id, $post_id );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$this->assertEquals( count($attendees), $attendees_list->get_attendance_counts( $post_id ) );
	}

	/**
	 * @test
	 *
	 * @covers \Tribe\Tickets\Events\Attendees_List::get_attendance_counts
	 */
	public function test_get_attendance_count_for_both_rsvp_and_tickets_commerce_attendees() {
		$post_id = $this->factory()->post->create();

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );

		$rsvp_attendees = $this->create_many_attendees_for_ticket( 16, $rsvp_ticket_id, $post_id );
		$ticket_attendees = $this->create_many_attendees_for_ticket( 5, $tickets_commerce_ticket_id, $post_id );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$total_attendees = count( $rsvp_attendees ) + count( $ticket_attendees );
		$this->assertEquals( $total_attendees, $attendees_list->get_attendance_counts( $post_id ) );
	}
}