<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use TEC\Tickets\Tests\Recurring_Tickets\Without_Recurrence_Tier;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * Filtering attendees by ticket type finds the attendees of rows, whose tickets are not posts.
 */
class Attendee_Type_Filter_Test extends WPTestCase {
	use Without_Recurrence_Tier;
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_find_attendees_of_rows_by_ticket_type(): void {
		[ $row_attendees, $default_attendees ] = $this->create_attendees();

		$this->assertEqualSets( $row_attendees, tribe_attendees()->where( 'ticket_type', Template_Guard::TICKET_TYPE )->get_ids() );
		$this->assertEqualSets( $default_attendees, tribe_attendees()->where( 'ticket_type', 'default' )->get_ids() );
		$this->assertEqualSets( $row_attendees, tribe_attendees()->where( 'ticket_type__not_in', 'default' )->get_ids() );
		$this->assertEqualSets( $default_attendees, tribe_attendees()->where( 'ticket_type__not_in', Template_Guard::TICKET_TYPE )->get_ids() );
	}

	/**
	 * @return array{0: int[], 1: int[]} The attendees of a row, and those of a default ticket.
	 */
	private function create_attendees(): array {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
		$row     = $this->insert_ticket_row( [ 'parent_id' => $template, 'post_id' => $event, 'occurrence_id' => $this->get_dates( $event )[0]->occurrence_id ] );
		$single  = tribe_events()->set_args( [ 'title' => 'Single', 'status' => 'publish', 'start_date' => '+2 days 10:00', 'end_date' => '+2 days 12:00' ] )->create()->ID;
		$default = $this->create_tc_ticket( $single, 5 );

		$this->create_row_order( [ $row => 2 ] );
		$this->create_row_order( [ $default => 1 ] );

		$row_attendees     = tribe_attendees()->where( 'ticket', $row )->get_ids();
		$default_attendees = tribe_attendees()->where( 'ticket', $default )->get_ids();
		$this->assertCount( 2, $row_attendees );
		$this->assertCount( 1, $default_attendees );

		return [ $row_attendees, $default_attendees ];
	}
}
