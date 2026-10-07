<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Events_Pro\Custom_Tables\V1\Updates\Events;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * "This event" turns a date's rows into real tickets of the new single event; trashing a date strands its attendees.
 */
class Detach_Test extends WPTestCase {
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
	public function it_should_turn_a_detached_dates_rows_into_tickets_of_the_single_event(): void {
		[ $event, $dates, $sold, $attendees ] = $this->create_event_with_a_sale();

		$single = (int) tribe( Events::class )->detach_occurrence_from_event( $dates[2] );

		$tickets = get_posts( [ 'post_type' => Ticket::POSTTYPE, 'fields' => 'ids', 'meta_key' => Ticket::$event_relation_meta_key, 'meta_value' => $single ] );
		$this->assertCount( 1, $tickets );
		$ticket = (int) $tickets[0];
		$this->assertSame( 'default', get_post_meta( $ticket, '_type', true ) );
		$this->assertSame( 'General Admission', get_post( $ticket )->post_title );
		$this->assertSame( [ '10', '8', '2' ], [ get_post_meta( $ticket, '_tribe_ticket_capacity', true ), get_post_meta( $ticket, '_stock', true ), get_post_meta( $ticket, 'total_sales', true ) ] );
		$this->assertEquals( 10, get_post_meta( $ticket, '_price', true ) );

		foreach ( $attendees as $attendee ) {
			$this->assertSame( (string) $ticket, get_post_meta( $attendee, '_tec_tickets_commerce_ticket', true ) );
			$this->assertSame( (string) $single, get_post_meta( $attendee, '_tec_tickets_commerce_event', true ) );
			$this->assertSame( '', get_post_meta( $attendee, '_tec_tickets_recurring_post_id', true ), 'No longer an attendee of a row.' );
		}

		$this->assertNull( tribe( Rows::class )->find( (int) $sold->id ) );
		$this->assertCount( 4, tribe( Rows::class )->get_by_post( $event ), 'The other dates keep their rows.' );
	}

	/**
	 * @test
	 */
	public function it_should_move_the_orders_of_a_detached_date_to_its_ticket(): void {
		[ , $dates, $sold ] = $this->create_event_with_a_sale();
		$row_ticket         = Ticket_ID::from_row_id( (int) $sold->id );
		// A buyer's payment is still on its way.
		$pending = $this->create_row_order( [ $row_ticket => 1 ], Pending::SLUG );

		$single = (int) tribe( Events::class )->detach_occurrence_from_event( $dates[2] );
		$ticket = (int) get_posts( [ 'post_type' => Ticket::POSTTYPE, 'fields' => 'ids', 'meta_key' => Ticket::$event_relation_meta_key, 'meta_value' => $single ] )[0];

		$items = array_values( (array) get_post_meta( $pending->ID, Order::$items_meta_key, true ) );
		$this->assertSame( [ $ticket, $single ], [ (int) $items[0]['ticket_id'], (int) $items[0]['event_id'] ] );
		$this->assertContains( (string) $ticket, get_post_meta( $pending->ID, Order::$tickets_in_order_meta_key ) );
		$this->assertNotContains( (string) $row_ticket, get_post_meta( $pending->ID, Order::$tickets_in_order_meta_key ) );
		$this->assertContains( (string) $single, get_post_meta( $pending->ID, Order::$events_in_order_meta_key ) );

		// The payment arrives.
		tribe( Order::class )->modify_status( $pending->ID, Completed::SLUG );

		$this->assertCount( 1, get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $pending->ID, 'post_status' => 'any', 'fields' => 'ids', 'meta_key' => '_tec_tickets_commerce_ticket', 'meta_value' => $ticket ] ) );
		$this->assertSame( '7', get_post_meta( $ticket, '_stock', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_the_attendees_of_other_tickets_alone(): void {
		$other        = tribe_events()->set_args( [ 'title' => 'Other', 'status' => 'publish', 'start_date' => '+1 week 10:00:00', 'end_date' => '+1 week 12:00:00' ] )->create()->ID;
		$other_ticket = $this->create_tc_ticket( $other, 5 );
		$other_order  = $this->create_row_order( [ $other_ticket => 1 ] );
		$bystander    = (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $other_order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )[0];
		[ , $dates ] = $this->create_event_with_a_sale();

		tribe( Events::class )->detach_occurrence_from_event( $dates[2] );

		$this->assertSame( (string) $other_ticket, get_post_meta( $bystander, '_tec_tickets_commerce_ticket', true ) );
		$this->assertSame( (string) $other, get_post_meta( $bystander, '_tec_tickets_commerce_event', true ) );
	}

	/**
	 * @test
	 */
	public function it_should_strand_the_attendees_of_a_trashed_date(): void {
		[ $event, $dates, $sold, $attendees ] = $this->create_event_with_a_sale();
		$date_id                              = (int) tribe( ID_Generator::class )->provide_id( (int) $dates[2]->occurrence_id );

		// What trashing one date does: ECP detaches it, then trashes the single event.
		do_action( 'trashed_post', $date_id );

		$this->assertNull( tribe( Rows::class )->find( (int) $sold->id ) );
		foreach ( $attendees as $attendee ) {
			$this->assertSame( (string) Ticket_ID::from_row_id( (int) $sold->id ), get_post_meta( $attendee, '_tec_tickets_commerce_ticket', true ) );
			$this->assertSame( (string) $date_id, get_post_meta( $attendee, '_tec_tickets_commerce_event', true ) );
			$this->assertSame( (string) $event, get_post_meta( $attendee, '_tec_tickets_recurring_post_id', true ), 'A stranded attendee keeps its event.' );
		}
		$this->assertCount( 4, tribe( Rows::class )->get_by_post( $event ) );
	}

	/**
	 * @return array{0: int, 1: object[], 2: object, 3: int[]} The event, its dates, the third date's row and its two
	 *                                                         attendees.
	 */
	private function create_event_with_a_sale(): array {
		$event = $this->create_recurring_event( 5 );
		$this->create_tc_ticket( $event, 10, [ 'ticket_name' => 'General Admission', 'tribe-ticket' => [ 'mode' => 'own', 'capacity' => 10 ] ] );
		$dates = $this->get_dates( $event );
		$sold  = null;
		foreach ( tribe( Rows::class )->get_by_post( $event ) as $row ) {
			if ( (int) $row->occurrence_id === (int) $dates[2]->occurrence_id ) {
				$sold = $row;
			}
		}
		$order     = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $sold->id ) => 2 ] );
		$attendees = array_map( 'intval', get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] ) );

		return [ $event, $dates, $sold, $attendees ];
	}
}
