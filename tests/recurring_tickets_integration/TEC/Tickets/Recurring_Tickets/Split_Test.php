<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Events\Custom_Tables\V1\Updates\Requests;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Updates\Update_Controllers\Upcoming;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Events_Pro\Tests\Traits\CT1\CT1_Fixtures;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * "This and following" moves the later dates' rows to the new event, with their sales.
 */
class Split_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;
	use CT1_Fixtures;

	/**
	 * @before
	 */
	public function edit_as_an_editor(): void {
		( new Tickets() )->empty_table();
		// An administrator's first admin screen redirects to TEC's first-time setup, and exits.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		set_current_screen( 'edit-post' );
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		set_current_screen( 'front' );
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_move_the_later_dates_rows_to_the_new_event_with_their_sales(): void {
		[ $event, $template, $dates, $sold, $attendees ] = $this->create_event_with_a_sale();

		$new = $this->split_at( $event, $dates[2] );

		// The new event also has rows for the dates its duplication gave it, which its own save prunes.
		$later = $this->ids( array_slice( $dates, 2 ) );
		$moved = array_filter( $this->rows_of( $new ), static fn( $row ) => in_array( (int) $row->occurrence_id, $later, true ) );
		$this->assertSame( $later, $this->occurrences( $moved ) );
		$clones = array_unique( array_map( static fn( $row ) => (int) $row->parent_id, $moved ) );
		$this->assertCount( 1, $clones );
		$clone = (int) reset( $clones );
		$this->assertNotSame( $template, $clone );
		$this->assertSame( (string) $new, get_post_meta( $clone, '_tec_tickets_commerce_event', true ) );
		$this->assertSame( Template_Guard::TICKET_TYPE, get_post_meta( $clone, '_type', true ) );

		$this->assertSame( $this->ids( array_slice( $dates, 0, 2 ) ), $this->occurrences( $this->rows_of( $event ) ), 'The earlier dates keep their rows.' );

		tribe( Rows::class )->forget( (int) $sold->id );
		$row = tribe( Rows::class )->find( (int) $sold->id );
		$this->assertSame( [ $new, $clone, 2 ], [ (int) $row->post_id, (int) $row->parent_id, (int) $row->sales ] );
		foreach ( $attendees as $attendee ) {
			$this->assertSame( (string) Ticket_ID::from_row_id( (int) $sold->id ), get_post_meta( $attendee, '_tec_tickets_commerce_ticket', true ) );
		}

		// The new event's own save keeps them.
		tribe( Sync::class )->sync_event( $new );
		$this->assertSame( $later, array_values( array_intersect( $this->occurrences( $this->rows_of( $new ) ), $later ) ) );
		$this->assertSame( 2, (int) tribe( Rows::class )->find( (int) $sold->id )->sales );
	}

	/**
	 * @test
	 */
	public function it_should_copy_a_template_the_duplication_left_out(): void {
		[ $event, $template, $dates ] = $this->create_event_with_a_sale();
		// Outside the admin the event's ticket list leaves templates out, so the duplication copies none.
		set_current_screen( 'front' );

		$new = $this->split_at( $event, $dates[2] );

		$later   = $this->ids( array_slice( $dates, 2 ) );
		$moved   = array_filter( $this->rows_of( $new ), static fn( $row ) => in_array( (int) $row->occurrence_id, $later, true ) );
		$parents = array_unique( array_map( static fn( $row ) => (int) $row->parent_id, $moved ) );
		$this->assertCount( 1, $parents );
		$this->assertNotSame( $template, (int) reset( $parents ) );
		$this->assertSame( (string) $new, get_post_meta( (int) reset( $parents ), '_tec_tickets_commerce_event', true ) );
	}

	/**
	 * @return array{0: int, 1: int, 2: object[], 3: object, 4: int[]} The event, its template, its dates, the fourth
	 *                                                                  date's row and its two attendees.
	 */
	private function create_event_with_a_sale(): array {
		$event    = $this->create_recurring_event( 5 );
		$template = $this->create_tc_ticket( $event, 10 );
		$dates    = $this->get_dates( $event );
		$sold     = null;
		foreach ( $this->rows_of( $event ) as $row ) {
			if ( (int) $row->occurrence_id === (int) $dates[3]->occurrence_id ) {
				$sold = $row;
			}
		}
		$order     = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $sold->id ) => 2 ] );
		$attendees = array_map( 'intval', get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] ) );

		return [ $event, $template, $dates, $sold, $attendees ];
	}

	/**
	 * Splits an event with "This and following", as ECP does on save.
	 *
	 * @param int    $event The event.
	 * @param object $date  The date to split at.
	 *
	 * @return int The new event.
	 */
	private function split_at( int $event, object $date ): int {
		$request        = tribe( Requests::class )->from_http_request_with_dates( $date->start_date, $date->end_date, 'UTC' );
		$provisional_id = tribe( ID_Generator::class )->provide_id( $date->occurrence_id );
		$request->set_param( 'id', $provisional_id );
		$request    = $this->add_block_params( $request, get_post_meta( $event, '_EventRecurrence', true ) );
		$controller = tribe( Upcoming::class );
		$controller->set_request( $request );
		$controller->set_occurrence( $date );

		return (int) $controller->apply_before_identify_step( $provisional_id );
	}

	/**
	 * @param int $post_id The event.
	 *
	 * @return object[] Its rows.
	 */
	private function rows_of( int $post_id ): array {
		return tribe( Rows::class )->get_by_post( $post_id );
	}

	/**
	 * @param object[] $rows The rows.
	 *
	 * @return int[] Their occurrence IDs, sorted.
	 */
	private function occurrences( array $rows ): array {
		$ids = array_values( array_unique( array_map( static fn( $row ) => (int) $row->occurrence_id, $rows ) ) );
		sort( $ids );

		return $ids;
	}

	/**
	 * @param object[] $dates The dates.
	 *
	 * @return int[] Their occurrence IDs, sorted.
	 */
	private function ids( array $dates ): array {
		$ids = array_map( static fn( $date ) => (int) $date->occurrence_id, $dates );
		sort( $ids );

		return $ids;
	}
}
