<?php

namespace TEC\Tickets\Recurring_Tickets;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A recurring event deleted for good takes its rows with it; its attendees and orders stay.
 */
class Event_Deletion_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

	/**
	 * @before
	 */
	public function edit_as_an_administrator(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

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
	public function it_should_delete_the_rows_of_a_deleted_event_and_keep_its_attendees(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );
		$rows  = tribe( Rows::class )->get_by_post( $event );
		$order = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $rows[0]->id ) => 1 ] );

		wp_delete_post( $event, true );

		$this->assertSame( [], tribe( Rows::class )->get_by_post( $event ) );
		$this->assertCount( 1, get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] ) );
		$this->assertInstanceOf( \WP_Post::class, get_post( $order->ID ) );
	}

	/**
	 * @test
	 */
	public function it_should_keep_the_rows_of_a_trashed_event(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );

		wp_trash_post( $event );

		$this->assertCount( 3, tribe( Rows::class )->get_by_post( $event ) );
	}
}
