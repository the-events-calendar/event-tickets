<?php

namespace TEC\Tickets\Recurring_Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Admin\Tickets_By_Date;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Recurrence_Tier_Check;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;

class Block_Editor_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;
	use Recurrence_Tier_Check;

	/**
	 * @before
	 */
	public function log_in(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_list_a_dates_copy_of_a_recurring_event_ticket_in_the_block_editor(): void {
		$this->skip_without_recurrence_tier();

		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		$date     = absint( $this->get_dates( $event )[1]->provisional_id );

		$listed = json_decode( tribe( 'tickets.editor.meta' )->register_tickets_list_in_rest( null, $date, '_tribe_tickets_list', true )[0], true );

		$table_tickets = array_filter( array_column( $listed, 'id' ), [ Ticket_ID::class, 'is_table_ticket' ] );
		$this->assertSame( [], $table_tickets, "The date's block already holds the event's ticket {$template}." );
	}

	/**
	 * @test
	 */
	public function it_should_send_tickets_by_date_with_an_event_the_editor_loads_or_saves(): void {
		$this->skip_without_recurrence_tier();
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );

		$data = $this->rest_event( $event, 'edit' );

		$this->assertArrayHasKey( 'tec_tickets_by_date', $data );
		$section = $data['tec_tickets_by_date'];
		unset( $section['sales'] );
		$this->assertSame( tribe( Tickets_By_Date::class )->for_event( $event ), $section );
		$date = (int) $this->get_dates( $event )[1]->provisional_id;
		$this->assertSame( $data['tec_tickets_by_date'], $this->rest_event( $date, 'edit' )['tec_tickets_by_date'], 'A date\'s editor shows its event\'s dates.' );
	}

	/**
	 * @test
	 */
	public function it_should_send_no_tickets_by_date_for_an_event_without_recurring_event_tickets(): void {
		$this->skip_without_recurrence_tier();

		$this->assertNull( $this->rest_event( $this->create_recurring_event(), 'edit' )['tec_tickets_by_date'] );
	}

	/**
	 * @test
	 */
	public function it_should_leave_tickets_by_date_out_of_an_event_viewed(): void {
		$this->skip_without_recurrence_tier();
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );

		$this->assertArrayNotHasKey( 'tec_tickets_by_date', $this->rest_event( $event, 'view' ) );
	}

	/**
	 * @test
	 */
	public function it_should_send_each_tickets_sales_over_every_date_as_the_attendees_page_counts_them(): void {
		$this->skip_without_recurrence_tier();
		$event  = $this->create_recurring_event();
		$shared = [ 'mode' => 'global', 'event_capacity' => 10, 'capacity' => 10 ];
		$adult  = $this->create_tc_ticket( $event, 10, [ 'ticket_name' => 'Adult', 'tribe-ticket' => $shared ] );
		$child  = $this->create_tc_ticket( $event, 5, [ 'ticket_name' => 'Child', 'tribe-ticket' => $shared ] );
		$own    = $this->create_tc_ticket( $event, 20, [ 'ticket_name' => 'Parking', 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 5 ] ] );
		$dates  = $this->get_dates( $event );
		$rows   = array_filter( tribe( Rows::class )->get_by_template( $adult ), static fn( $row ) => (int) $row->occurrence_id === (int) $dates[0]->occurrence_id );
		$this->create_row_order( [ Ticket_ID::from_row_id( (int) reset( $rows )->id ) => 2 ] );
		$this->forget_rows_read();

		$sales = $this->rest_event( $event, 'edit' )['tec_tickets_by_date']['sales'];

		// A pool of 10 on each of the 3 dates, 2 sold; Parking has 5 of its own on each.
		$left = 3 * 10 - 2;
		$this->assertSame(
			[
				'tickets'   => [
					$adult => [ 'sold' => 2, 'available' => $left ],
					$child => [ 'sold' => 0, 'available' => $left ],
					$own   => [ 'sold' => 0, 'available' => 3 * 5 ],
				],
				'sold'      => 2,
				'available' => $left + 3 * 5,
				'shared'    => [ 'sold' => 2, 'capacity' => 3 * 10 ],
			],
			$sales
		);
	}

	private function rest_event( int $event, string $context ): array {
		$request = new WP_REST_Request( 'GET', rest_get_route_for_post( $event ) );
		$request->set_param( 'context', $context );

		return (array) rest_get_server()->response_to_data( rest_do_request( $request ), false );
	}
}
