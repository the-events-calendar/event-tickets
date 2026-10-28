<?php

namespace TEC\Tickets\Recurring_Tickets\Editor;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Admin\Tickets_By_Date;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Recurrence_Tier_Check;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_REST_Request;

class Block_Editor_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
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
		$this->assertSame( tribe( Tickets_By_Date::class )->for_event( $event ), $data['tec_tickets_by_date'] );
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

	private function rest_event( int $event, string $context ): array {
		$request = new WP_REST_Request( 'GET', rest_get_route_for_post( $event ) );
		$request->set_param( 'context', $context );

		return (array) rest_get_server()->response_to_data( rest_do_request( $request ), false );
	}
}
