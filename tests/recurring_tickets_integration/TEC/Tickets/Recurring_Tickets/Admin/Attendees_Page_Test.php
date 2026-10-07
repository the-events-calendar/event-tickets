<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Tickets__Tickets as Tickets_Tickets;

/**
 * A recurring event lists the attendees of all its dates, also of a gone date, which it marks.
 */
class Attendees_Page_Test extends WPTestCase {
	use Ticket_Rows;
	use Ticket_Maker;
	use Row_Orders;

	/**
	 * The event.
	 *
	 * @var int
	 */
	private int $event = 0;

	/**
	 * The attendee of the first date, still there.
	 *
	 * @var int
	 */
	private int $kept = 0;

	/**
	 * The attendee of the second date, whose row is gone.
	 *
	 * @var int
	 */
	private int $stranded = 0;

	/**
	 * @before
	 */
	public function create_attendees(): void {
		( new Tickets() )->empty_table();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->event = $this->create_recurring_event();
		$this->create_tc_ticket( $this->event, 10, [ 'ticket_name' => 'General Admission' ] );
		$dates = $this->get_dates( $this->event );
		$rows  = [];
		foreach ( tribe( Rows::class )->get_by_post( $this->event ) as $row ) {
			$rows[ (int) $row->occurrence_id ] = $row;
		}
		$first  = $rows[ (int) $dates[0]->occurrence_id ];
		$second = $rows[ (int) $dates[1]->occurrence_id ];

		$this->kept     = $this->attendee_of( $this->create_row_order( [ Ticket_ID::from_row_id( (int) $first->id ) => 1 ] )->ID );
		$this->stranded = $this->attendee_of( $this->create_row_order( [ Ticket_ID::from_row_id( (int) $second->id ) => 1 ] )->ID );
		// The second date's row goes, as when its date is removed.
		tribe( Rows::class )->delete_ids( [ (int) $second->id ] );
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
		unset( $_GET['event_id'], $_GET[ Attendees_Page::DATE_FILTER ] );
	}

	/**
	 * @test
	 */
	public function it_should_list_the_attendees_of_every_date_on_the_event(): void {
		$ids = array_map( 'intval', tribe_attendees()->where( 'event', $this->event )->get_ids() );
		sort( $ids );

		$this->assertSame( [ $this->kept, $this->stranded ], $ids );
		$this->assertSame( 2, Tickets_Tickets::get_event_attendees_count( $this->event ) );
	}

	/**
	 * @test
	 */
	public function it_should_list_the_event_afresh_once_an_attendee_is_deleted(): void {
		$this->assertCount( 2, Tickets_Tickets::get_event_attendees( $this->event ) );

		// As the attendees table deletes one.
		tribe( \TEC\Tickets\Commerce\Attendee::class )->delete( $this->stranded );

		$this->assertSame( [ $this->kept ], array_map( static fn( array $attendee ) => (int) $attendee['attendee_id'], Tickets_Tickets::get_event_attendees( $this->event ) ) );
	}

	/**
	 * @test
	 */
	public function it_should_mark_the_attendee_of_a_gone_date_stranded(): void {
		$this->assertStringContainsString( 'Stranded', $this->ticket_cell( $this->stranded ) );
		$this->assertStringNotContainsString( 'Stranded', $this->ticket_cell( $this->kept ) );
	}

	/**
	 * @test
	 */
	public function it_should_name_a_rows_ticket_and_give_its_date_on_my_tickets(): void {
		$attendees = Tickets_Tickets::get_event_attendees_by_args( $this->event, [ 'in' => [ $this->kept ] ] )['attendees'];
		$this->assertTrue( (bool) $attendees[0]['ticket_exists'] );

		ob_start();
		do_action( 'tec_tickets_my_tickets_ticket_information_after_ticket_name', $attendees[0] );
		$html = (string) ob_get_clean();

		$start = get_post_meta( $this->kept, '_tec_tickets_recurring_occurrence_start', true );
		$this->assertStringContainsString( esc_html( tribe_format_date( $start, true ) ), $html );
	}

	/**
	 * @test
	 */
	public function it_should_show_each_attendees_date_in_a_column(): void {
		$this->assertArrayHasKey( Attendees_Page::DATE_COLUMN, apply_filters( 'tribe_tickets_attendee_table_columns', [], $this->event ) );
		$this->assertArrayNotHasKey( Attendees_Page::DATE_COLUMN, apply_filters( 'tribe_tickets_attendee_table_columns', [], static::factory()->post->create() ) );

		$start = get_post_meta( $this->stranded, '_tec_tickets_recurring_occurrence_start', true );
		$cell  = apply_filters( 'tribe_events_tickets_attendees_table_column', '', [ 'attendee_id' => $this->stranded ], Attendees_Page::DATE_COLUMN );
		$this->assertSame( esc_html( tribe_format_date( $start, true ) ), $cell );
	}

	/**
	 * @test
	 */
	public function it_should_offer_the_dates_its_attendees_hold_to_filter_by(): void {
		$_GET['event_id'] = $this->event;

		$nav    = apply_filters( 'tribe_events_tickets_attendees_table_nav', [ 'left' => [], 'right' => [] ], 'top' );
		$select = implode( '', $nav['left'] );

		foreach ( [ $this->kept, $this->stranded ] as $attendee ) {
			$date = (int) get_post_meta( $attendee, '_tec_tickets_commerce_event', true );
			$this->assertStringContainsString( 'value="' . $date . '"', $select, 'A removed date stays listed.' );
			$this->assertStringContainsString( esc_html( tribe_format_date( get_post_meta( $attendee, '_tec_tickets_recurring_occurrence_start', true ), true ) ), $select );
		}
		$this->assertSame( [ 'left' => [], 'right' => [] ], apply_filters( 'tribe_events_tickets_attendees_table_nav', [ 'left' => [], 'right' => [] ], 'bottom' ) );
	}

	/**
	 * @test
	 */
	public function it_should_narrow_the_table_to_a_chosen_date(): void {
		$_GET['event_id'] = $this->event;
		$_GET[ Attendees_Page::DATE_FILTER ] = (string) get_post_meta( $this->stranded, '_tec_tickets_commerce_event', true );

		$this->assertSame( [ $this->stranded ], $this->table_attendees() );

		// A date the event's attendees do not hold changes nothing.
		$_GET[ Attendees_Page::DATE_FILTER ] = (string) static::factory()->post->create();

		$this->assertSame( [ $this->kept, $this->stranded ], $this->table_attendees() );
	}

	/**
	 * @test
	 */
	public function it_should_clean_the_events_cached_attendees_with_its_dates(): void {
		$date = (int) get_post_meta( $this->kept, '_tec_tickets_commerce_event', true );
		tribe( 'post-transient' )->set( $this->event, Tickets_Tickets::ATTENDEES_CACHE, [ 'stale' ], HOUR_IN_SECONDS );

		// As a check-in or a status change of the date's attendee does.
		tribe( 'post-transient' )->delete( $date, Tickets_Tickets::ATTENDEES_CACHE );

		$this->assertFalse( tribe( 'post-transient' )->get( $this->event, Tickets_Tickets::ATTENDEES_CACHE ) );
	}

	/**
	 * @test
	 */
	public function it_should_leave_a_lookup_by_no_event_alone(): void {
		$this->assertSame( [ 0 ], apply_filters( 'tec_tickets_attendees_filter_by_event', [ 0 ], tribe_attendees() ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_query_for_a_post_that_is_not_an_event(): void {
		global $wpdb;
		$page = static::factory()->post->create( [ 'post_type' => 'page' ] );
		get_post( $page );
		$queries = $wpdb->num_queries;

		$this->assertSame( [ $page ], apply_filters( 'tec_tickets_attendees_filter_by_event', [ $page ], tribe_attendees() ) );
		$this->assertSame( $queries, $wpdb->num_queries );
	}

	/**
	 * @return int[] The attendees the event's Attendees table lists, sorted.
	 */
	private function table_attendees(): array {
		// An administrator's first admin screen redirects to TEC's first-time setup, and exits.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		set_current_screen( 'tribe_events_page_tickets-attendees' );
		$table = new \Tribe__Tickets__Attendees_Table();
		$table->prepare_items();
		set_current_screen( 'front' );
		$ids = array_map( static fn( array $item ) => (int) $item['attendee_id'], $table->items );
		sort( $ids );

		return $ids;
	}

	/**
	 * @param int $attendee_id The attendee.
	 *
	 * @return string What the attendees table adds to the attendee's ticket cell.
	 */
	private function ticket_cell( int $attendee_id ): string {
		$item = Tickets_Tickets::get_event_attendees_by_args( $this->event, [ 'in' => [ $attendee_id ] ] )['attendees'][0];

		ob_start();
		do_action( 'event_tickets_attendees_table_ticket_column', $item );

		return (string) ob_get_clean();
	}

	/**
	 * @param int $order_id The order.
	 *
	 * @return int The order's attendee.
	 */
	private function attendee_of( int $order_id ): int {
		return (int) get_posts( [ 'post_type' => 'tec_tc_attendee', 'post_parent' => $order_id, 'post_status' => 'any', 'fields' => 'ids' ] )[0];
	}
}
