<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use ReflectionMethod;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Post_History as Post_History;
use Tribe__Tickets__Admin__Move_Tickets as Move_Tickets;
use Tribe__Tickets__Main as Main;

/**
 * Move Tickets moves an attendee of a recurring event ticket, stranded or not, to another date.
 */
class Move_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

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
	public function it_should_offer_dates_as_destinations_when_searching_events(): void {
		$event = $this->create_recurring_event();
		$dates = $this->get_dates( $event );

		// Move Tickets searches over AJAX.
		add_filter( 'wp_doing_ajax', '__return_true' );

		$matches = array_map( 'intval', $this->call( 'get_possible_matches', [ [ 'post_type' => [ 'tribe_events' ], 'search_terms' => 'Recurring' ] ] ) );

		// The first date is listed under the event's own ID.
		$this->assertContains( $event, $matches );
		foreach ( array_slice( $dates, 1 ) as $date ) {
			$this->assertContains( (int) $date->provisional_id, $matches, 'Each date is a destination.' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_offer_a_dates_rows_and_never_a_template_as_ticket_types(): void {
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10, [ 'ticket_name' => 'General Admission' ] );
		$date     = $this->get_dates( $event )[2];
		$row      = $this->row_on( $event, $date );

		// The provider comes slashed from the request.
		$provider = addslashes( Module::class );
		$this->assertSame( [ Ticket_ID::from_row_id( (int) $row->id ) => 'General Admission' ], $this->call( 'get_ticket_type_matches', [ (int) $date->provisional_id, $provider ] ) );

		// The event's own ID stands for its first date in a list of dates.
		$first = $this->row_on( $event, $this->get_dates( $event )[0] );
		$this->assertSame( [ Ticket_ID::from_row_id( (int) $first->id ) => 'General Admission' ], $this->call( 'get_ticket_type_matches', [ $event, $provider ] ) );
		$this->assertArrayNotHasKey( $template, $this->call( 'get_ticket_type_matches', [ $event, $provider ] ) );
	}

	/**
	 * @test
	 */
	public function it_should_move_a_stranded_attendee_to_another_dates_row(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10, [ 'ticket_name' => 'General Admission', 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 10 ] ] );
		$dates  = $this->get_dates( $event );
		$gone   = $this->row_on( $event, $dates[1] );
		$target = $this->row_on( $event, $dates[2] );
		$order  = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $gone->id ) => 1 ] );
		$attendee = (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )[0];
		// The second date's row goes, as when its date is removed.
		tribe( Rows::class )->delete_ids( [ (int) $gone->id ] );

		$target_ticket = Ticket_ID::from_row_id( (int) $target->id );
		$moved         = Main::instance()->move_tickets()->move_tickets( [ $attendee ], $target_ticket, $event, (int) $dates[2]->provisional_id );

		$this->assertSame( 1, $moved );
		$this->assertSame( (string) $target_ticket, get_post_meta( $attendee, '_tec_tickets_commerce_ticket', true ) );
		$this->assertSame( (string) $dates[2]->provisional_id, get_post_meta( $attendee, '_tec_tickets_commerce_event', true ) );
		$this->assertSame( $dates[2]->start_date, get_post_meta( $attendee, '_tec_tickets_recurring_occurrence_start', true ) );

		tribe( Rows::class )->forget( (int) $target->id );
		$row = tribe( Rows::class )->find( (int) $target->id );
		$this->assertSame( [ 1, 9 ], [ (int) $row->sales, (int) $row->stock ] );

		$history = wp_json_encode( Post_History::load( $attendee )->get_entries() );
		$this->assertStringContainsString( 'General Admission', $history );

		// Its order is the new date's too, so the event's Orders page still finds it.
		$this->assertContains( (string) $dates[2]->provisional_id, get_post_meta( $order->ID, '_tec_tc_order_events_in_order' ) );
	}

	/**
	 * @test
	 */
	public function it_should_offer_no_template_of_a_recurring_event_without_ecp(): void {
		global $wp_actions;
		$event    = $this->create_recurring_event();
		$template = $this->create_tc_ticket( $event, 10 );
		$fired    = $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'];
		// As when ECP is not active: its dates, and their ID generator, are not there.
		unset( $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] );

		try {
			$matches = $this->call( 'get_ticket_type_matches', [ $event, addslashes( Module::class ) ] );
		} finally {
			$wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] = $fired;
		}

		$this->assertSame( [], $matches );
		$this->assertArrayNotHasKey( $template, $matches );
	}

	/**
	 * @test
	 */
	public function it_should_give_an_attendee_moved_through_the_events_own_id_its_rows_date(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );
		$dates    = $this->get_dates( $event );
		$order    = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $this->row_on( $event, $dates[1] )->id ) => 1 ] );
		$attendee = (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )[0];
		$first    = Ticket_ID::from_row_id( (int) $this->row_on( $event, $dates[0] )->id );

		Main::instance()->move_tickets()->move_tickets( [ $attendee ], $first, $event, $event );

		$this->assertSame( (string) $dates[0]->provisional_id, get_post_meta( $attendee, '_tec_tickets_commerce_event', true ) );
	}

	/**
	 * Calls one of Move Tickets' protected methods.
	 *
	 * @param string $method    The method.
	 * @param array  $arguments Its arguments.
	 *
	 * @return mixed What it returns.
	 */
	private function call( string $method, array $arguments ) {
		$reflection = new ReflectionMethod( Move_Tickets::class, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( Main::instance()->move_tickets(), $arguments );
	}

	/**
	 * @param int    $event The event.
	 * @param object $date  The date.
	 *
	 * @return object The event's row on that date.
	 */
	private function row_on( int $event, object $date ): object {
		$rows = array_filter( tribe( Rows::class )->get_by_post( $event ), static fn( $row ) => (int) $row->occurrence_id === (int) $date->occurrence_id );

		return reset( $rows );
	}
}
