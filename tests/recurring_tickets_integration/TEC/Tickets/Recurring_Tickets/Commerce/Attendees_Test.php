<?php

namespace TEC\Tickets\Recurring_Tickets\Commerce;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * An attendee of a row keeps its real event and its date's start.
 */
class Attendees_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

	/**
	 * @after
	 */
	public function clean_up(): void {
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_store_the_event_and_the_dates_start_on_a_rows_attendee(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$event  = $this->create_recurring_event();
		$second = $this->get_dates( $event )[1];
		$this->create_tc_ticket( $event, 10 );
		$rows = array_filter( tribe( Rows::class )->get_by_post( $event ), static fn( $row ) => (int) $row->occurrence_id === (int) $second->occurrence_id );
		$row  = reset( $rows );

		$order    = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $row->id ) => 1 ] );
		$attendee = (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )[0];

		$this->assertSame( (string) $event, get_post_meta( $attendee, Attendees::POST_ID_META_KEY, true ) );
		$this->assertSame( $second->start_date, get_post_meta( $attendee, Attendees::OCCURRENCE_START_META_KEY, true ) );
	}

	/**
	 * @test
	 */
	public function it_should_store_nothing_on_another_tickets_attendee(): void {
		$event    = tribe_events()->set_args( [ 'title' => 'Single', 'status' => 'publish', 'start_date' => '+1 week 10:00:00', 'end_date' => '+1 week 12:00:00' ] )->create()->ID;
		$ticket   = $this->create_tc_ticket( $event, 10 );
		$order    = $this->create_row_order( [ $ticket => 1 ] );
		$attendee = (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )[0];

		$this->assertSame( '', get_post_meta( $attendee, '_tec_tickets_recurring_post_id', true ) );
	}
}
