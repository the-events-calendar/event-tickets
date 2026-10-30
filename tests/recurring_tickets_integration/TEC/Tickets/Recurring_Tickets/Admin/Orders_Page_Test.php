<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Admin_Tables\Orders as Orders_Table;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

/**
 * A recurring event's Orders page lists the orders of all its dates, shows each order's dates and narrows to one.
 */
class Orders_Page_Test extends WPTestCase {
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
	 * The dates of the event.
	 *
	 * @var object[]
	 */
	private array $dates = [];

	/**
	 * The orders of the first and the second date.
	 *
	 * @var int[]
	 */
	private array $orders = [];

	/**
	 * @before
	 */
	public function create_orders(): void {
		( new Tickets() )->empty_table();
		$this->event = $this->create_recurring_event();
		$this->create_tc_ticket( $this->event, 10 );
		$this->dates = $this->get_dates( $this->event );
		$rows        = array_column( tribe( Rows::class )->get_by_post( $this->event ), null, 'occurrence_id' );
		foreach ( [ 0, 1 ] as $date ) {
			$row            = $rows[ $this->dates[ $date ]->occurrence_id ];
			$this->orders[] = $this->create_row_order( [ Ticket_ID::from_row_id( (int) $row->id ) => 1 ] )->ID;
		}
		// An administrator's first admin screen redirects to TEC's first-time setup, and exits.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'editor' ] ) );
		set_current_screen( 'tribe_events_page_tickets-commerce-orders' );
	}

	/**
	 * @after
	 */
	public function clean_up(): void {
		set_current_screen( 'front' );
		unset( $_GET['post_id'], $_GET[ Date_Filter::NAME ] );
		// Completing an order commits the test's database transaction.
		( new Tickets() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_list_the_orders_of_every_date_on_the_event(): void {
		$this->open_the_events_page();

		$this->assertSame( $this->orders, $this->listed_orders() );

		// So does its export.
		$events = apply_filters( 'tec_tc_order_report_export_args', [ 'events' => $this->event ] )['events'];
		foreach ( $this->dates as $date ) {
			$this->assertContains( (int) $date->provisional_id, $events );
		}
	}

	/**
	 * @test
	 */
	public function it_should_show_each_orders_date_in_a_column(): void {
		$this->open_the_events_page();
		$table = new Orders_Table();
		$this->assertArrayHasKey( Orders_Page::DATE_COLUMN, $table->get_columns() );

		$order = tec_tc_get_order( $this->orders[1] );
		$this->assertSame( esc_html( tribe_format_date( $this->dates[1]->start_date, true ) ), $table->column_default( $order, Orders_Page::DATE_COLUMN ) );

		$_GET['post_id'] = static::factory()->post->create();
		$this->assertArrayNotHasKey( Orders_Page::DATE_COLUMN, $table->get_columns() );
	}

	/**
	 * @test
	 */
	public function it_should_offer_the_dates_to_filter_by(): void {
		$this->open_the_events_page();
		ob_start();
		( new Orders_Table() )->extra_tablenav( 'top' );
		$nav = (string) ob_get_clean();

		$this->assertStringContainsString( '<select name="' . Date_Filter::NAME . '"', $nav );
		foreach ( $this->dates as $date ) {
			$this->assertStringContainsString( 'value="' . (int) $date->provisional_id . '"', $nav, 'Every date is listed, with orders or not.' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_narrow_the_orders_to_a_chosen_date(): void {
		$this->open_the_events_page();
		$_GET[ Date_Filter::NAME ] = (string) $this->dates[1]->provisional_id;

		$this->assertSame( [ $this->orders[1] ], $this->listed_orders() );

		// A date not of the event changes nothing.
		$_GET[ Date_Filter::NAME ] = (string) static::factory()->post->create();

		$this->assertSame( $this->orders, $this->listed_orders() );
	}

	/**
	 * @test
	 */
	public function it_should_not_offer_the_event_as_its_own_date_without_ecp(): void {
		global $wp_actions;
		$this->open_the_events_page();
		$fired = $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'];
		// As when ECP is not active: a row then names its event, not its date.
		unset( $wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] );

		try {
			ob_start();
			( new Orders_Table() )->extra_tablenav( 'top' );
			$nav = (string) ob_get_clean();
		} finally {
			$wp_actions['tec_events_pro_custom_tables_v1_fully_activated'] = $fired;
		}

		$this->assertStringNotContainsString( 'value="' . $this->event . '"', $nav );
	}

	/**
	 * Opens the event's Orders page; the test case's set up, which runs after the fixture, empties `$_GET`.
	 *
	 * @return void
	 */
	private function open_the_events_page(): void {
		$_GET['post_id'] = $this->event;
	}

	/**
	 * @return int[] The orders the event's Orders table lists, sorted.
	 */
	private function listed_orders(): array {
		$table = new Orders_Table();
		$table->prepare_items();
		$ids = array_map( static fn( $order ) => (int) $order->ID, $table->items );
		sort( $ids );

		return $ids;
	}
}
