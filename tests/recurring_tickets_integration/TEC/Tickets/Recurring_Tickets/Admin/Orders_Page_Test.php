<?php

namespace TEC\Tickets\Recurring_Tickets\Admin;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Admin_Tables\Orders as Orders_Table;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Recurring_Tickets\Commerce\Attendees;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Template_Guard;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
use TEC\Tickets\Tests\Recurring_Tickets\Row_Orders;
use TEC\Tickets\Tests\Recurring_Tickets\Ticket_Rows;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Date_Utils as Dates;

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
		$template = $this->create_tc_ticket( $this->event, 10 );
		// As the ticket editor saves a ticket on a recurring event.
		update_post_meta( $template, '_type', Template_Guard::TICKET_TYPE );
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
	public function it_should_give_each_purchased_ticket_its_date(): void {
		$this->open_the_events_page();
		$table = new Orders_Table();
		$date  = tribe_format_date( $this->dates[1]->start_date, false, Dates::datepicker_formats( Dates::get_datepicker_format_index() ) );

		$this->assertStringContainsString( ' - ' . esc_html( $date ) . '</div>', $table->column_purchased( tec_tc_get_order( $this->orders[1] ) ) );

		// The date has its own column no more.
		$this->assertArrayNotHasKey( 'tec_tickets_recurring_date', $table->get_columns() );
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
		$this->assertStringContainsString( 'Orders for:', $nav );
		$this->assertStringContainsString( 'value="' . Date_Filter::UPCOMING . '"', $nav );
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
	public function it_should_narrow_the_orders_to_the_upcoming_dates(): void {
		$this->open_the_events_page();
		$_GET[ Date_Filter::NAME ] = Date_Filter::UPCOMING;

		$this->assertSame( $this->orders, $this->listed_orders(), 'Every date of the event is to come.' );
	}

	/**
	 * @test
	 */
	public function it_should_list_no_order_when_no_date_is_to_come(): void {
		global $wpdb;
		$past = '2000-01-01 10:00:00';
		$wpdb->query( $wpdb->prepare( 'UPDATE %i SET occurrence_start = %s WHERE post_id = %d', Tickets::table_name(), $past, $this->event ) );
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE meta_key = %s", $past, Attendees::OCCURRENCE_START_META_KEY ) );
		$this->open_the_events_page();
		$_GET[ Date_Filter::NAME ] = Date_Filter::UPCOMING;

		$this->assertSame( [], $this->listed_orders(), 'No event at all would list every order of the site.' );
	}

	/**
	 * @test
	 */
	public function it_should_sum_the_sales_of_every_date_by_recurring_event_ticket(): void {
		$summary = $this->summary();

		$this->assertInstanceOf( Order_Summary::class, $summary );
		$sales = $summary->get_event_sales_data();
		$this->assertSame( 2, $sales['total_sales']['qty'] );
		$this->assertSame( 2, $sales['total_ordered']['qty'] );

		$tickets = $summary->get_tickets_by_type()[ Template_Guard::TICKET_TYPE ];
		$this->assertCount( 1, $tickets, 'One line per recurring event ticket, of every date.' );
		$this->assertSame( 2, $tickets[0]['qty_data'][ Completed::SLUG ] );
		$this->assertSame( 'Recurring Event Tickets · all dates', $summary->get_label_for_type( Template_Guard::TICKET_TYPE ) );
		$this->assertSame( 'All Dates', $summary->get_sales_totals_title() );
	}

	/**
	 * @test
	 */
	public function it_should_sum_the_sales_of_the_next_date_apart(): void {
		$summary = $this->summary();

		$this->assertSame( (int) $this->dates[0]->provisional_id, $summary->get_next_date() );
		$next = $summary->get_next_date_sales();
		$this->assertSame( 1, $next['total_sales']['qty'] );
		$this->assertSame( 1, $next['by_status'][ Completed::SLUG ]['qty_sold'] );

		ob_start();
		do_action( 'tec_tickets_commerce_order_report_before_sales_totals', $summary );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Next Date:', $html );
	}

	/**
	 * @test
	 */
	public function it_should_leave_the_summary_of_an_event_without_dates_alone(): void {
		$vars = [ 'post_id' => static::factory()->post->create() ];

		$this->assertSame( $vars, apply_filters( 'tec_tickets_commerce_reports_tickets-commerce-orders_template_vars', $vars ) );
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
	 * @return Order_Summary The summary the event's Orders page shows.
	 */
	private function summary(): Order_Summary {
		return apply_filters( 'tec_tickets_commerce_reports_tickets-commerce-orders_template_vars', [ 'post_id' => $this->event ] )['order_summary'];
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
