<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Stock;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * ECP's save and delete dialog warns how many upcoming dates have sold tickets.
 */
class Dialog_Warning_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;

	/**
	 * @before
	 */
	public function empty_rows(): void {
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_warn_how_many_upcoming_dates_have_sold_tickets(): void {
		$event = $this->create_recurring_event( 4 );
		$this->create_tc_ticket( $event, 10 );
		$rows = $this->rows_by_date( $event );
		foreach ( [ 0, 1, 3 ] as $date ) {
			tribe( Stock::class )->add_sales( (int) $rows[ $date ]->id, 1 );
		}
		// The fourth date is past: its sales are not at risk.
		DB::query( DB::prepare( 'UPDATE %i SET occurrence_start_utc = %s WHERE id = %d', Tickets::table_name(), '2020-01-01 10:00:00', (int) $rows[3]->id ) );

		$notices = $this->notices( $event );

		$this->assertCount( 1, $notices );
		$this->assertSame( 'any', $notices[0]['context'] );
		$this->assertTrue( $notices[0]['destructive'] );
		$this->assertStringContainsString( '2 upcoming dates have sold tickets', $notices[0]['text'] );
	}

	/**
	 * @test
	 */
	public function it_should_say_nothing_when_no_upcoming_date_has_sold_tickets(): void {
		$event = $this->create_recurring_event();
		$this->create_tc_ticket( $event, 10 );

		$this->assertSame( [], $this->notices( $event ) );
		$this->assertSame( [], $this->notices( 0 ) );
	}

	/**
	 * @param int $post_id The event being edited.
	 *
	 * @return array<array<string,mixed>> The dialog's notices.
	 */
	private function notices( int $post_id ): array {
		$data = apply_filters( 'tec_events_pro_custom_tables_v1_editor_dialog_data', [ 'notices' => [] ], $post_id );

		return $data['notices'];
	}

	/**
	 * @param int $event The event.
	 *
	 * @return object[] The event's rows, in date order.
	 */
	private function rows_by_date( int $event ): array {
		$rows = array_column( tribe( Rows::class )->get_by_post( $event ), null, 'occurrence_id' );

		return array_map( static fn( $date ) => $rows[ $date->occurrence_id ], $this->get_dates( $event ) );
	}
}
