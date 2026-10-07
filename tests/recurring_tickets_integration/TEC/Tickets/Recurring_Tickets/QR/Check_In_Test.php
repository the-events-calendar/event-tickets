<?php

namespace TEC\Tickets\Recurring_Tickets\QR;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Tickets\QR\Observer;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * An attendee whose date is gone can still be checked in by scanning its QR code.
 */
class Check_In_Test extends WPTestCase {
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
	public function it_should_check_in_a_stranded_attendee_against_its_event(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );
		$date = $this->get_dates( $event )[1];
		$rows = array_filter( tribe( Rows::class )->get_by_post( $event ), static fn( $row ) => (int) $row->occurrence_id === (int) $date->occurrence_id );
		$row  = reset( $rows );

		$order    = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $row->id ) => 1 ] );
		$attendee = (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order->ID, 'post_status' => 'any', 'fields' => 'ids' ] )[0];
		$code     = (string) get_post_meta( $attendee, '_tec_tickets_commerce_security_code', true );
		$date_id  = (int) $date->provisional_id;

		// The date is removed, and its row with it.
		DB::delete( Occurrences::table_name(), [ 'occurrence_id' => (int) $date->occurrence_id ] );
		tribe( Rows::class )->delete_ids( [ (int) $row->id ] );
		wp_cache_delete( $date_id, 'posts' );

		$result = tribe( Observer::class )->authorized_check_in( $date_id, $attendee, $code );

		$this->assertTrue( $result['user_had_access'] );
		$this->assertNotSame( '', $result['url'] );
		$this->assertNotEmpty( get_post_meta( $attendee, '_tribe_qr_status', true ), 'The attendee is checked in.' );
	}
}
