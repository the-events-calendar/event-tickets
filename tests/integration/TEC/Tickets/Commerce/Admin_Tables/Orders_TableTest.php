<?php

namespace TEC\Tickets\Commerce\Admin_Tables;

use TEC\Tickets\Commerce\Order;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Traits\With_Globals;
use WP_Screen;
use WP_Query;

class Orders_TableTest extends \Codeception\TestCase\WPTestCase {

	use SnapshotAssertions;
	use Order_Maker;
	use Ticket_Maker;
	use With_Uopz;
	use With_Globals;

	/**
	 * Created orders.
	 *
	 * @var array
	 */
	protected $orders;

	/**
	 * Created tickets.
	 *
	 * @var array
	 */
	protected $tickets;

	/**
	 * Created event IDs.
	 *
	 * @var array
	 */
	protected $event_ids;

	/**
	 * @before
	 */
	public function set_up() {
		$this->set_global_value( 'current_screen', WP_Screen::get( 'edit-' . Order::POSTTYPE ) );
		$this->set_global_value( 'typenow', Order::POSTTYPE );
	}

	/**
	 * @test
	 */
	public function it_should_match_single_row() {
		$this->prepare_tests_and_overwrite_wp_query();
		$orders_table = new Orders_Table();

		$orders_table->prepare_items();

		ob_start();
		$orders_table->single_row( get_post( $this->orders['0']->ID ) );
		$html = ob_get_clean();

		$html = str_replace( $this->orders['0']->ID, '{{order_id}}', $html );

		$html = preg_replace(
			'/<time datetime="(.*)" title="(.*)">(.*)<\/time>/',
			'<time datetime="{{order_date}}" title="{{order_date}}">{{order_date}}</time>',
			$html
		);

		$html = preg_replace(
			'/Test TC ticket for ([0-9]+)/',
			'Test TC ticket for {{ticket_id}}',
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_match_views() {
		$orders_table = new Orders_Table();

		$orders_table->prepare_items();

		ob_start();

		$orders_table->views();

		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_match_search_box() {
		$this->prepare_tests_and_overwrite_wp_query();
		$orders_table = new Orders_Table();

		$orders_table->prepare_items();

		ob_start();

		$orders_table->search_box( 'Search Orders', 'order' );

		$html = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_match_display() {
		$this->prepare_tests_and_overwrite_wp_query();
		$orders_table = new Orders_Table();

		$orders_table->prepare_items();

		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		ob_start();

		$orders_table->display();

		$html = ob_get_clean();

		$html = preg_replace(
			'/<time datetime="(.*)" title="(.*)">(.*)<\/time>/',
			'<time datetime="{{order_date}}" title="{{order_date}}">{{order_date}}</time>',
			$html
		);
		$html = preg_replace(
			'/id="tec_tc_order-([0-9]+)"/',
			'id="tec_tc_order-{{order_id}}"',
			$html
		);
		$html = preg_replace(
			'/#([0-9]+) Test Purchaser/',
			'#{{order_id}} Test Purchaser',
			$html
		);
		$html = preg_replace(
			'/Test TC ticket for ([0-9]+)/',
			'Test TC ticket for {{order_id}}',
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function it_should_match_expected_columns() {
		$orders_table = new Orders_Table();

		$expected_columns = [
			'order'            => __( 'Order', 'event-tickets' ),
			'date'             => __( 'Date', 'event-tickets' ),
			'status'           => __( 'Status', 'event-tickets' ),
			'items'            => __( 'Items', 'event-tickets' ),
			'total'            => __( 'Total', 'event-tickets' ),
			'gateway'          => __( 'Gateway', 'event-tickets' ),
			'gateway_order_id' => __( 'Gateway ID', 'event-tickets' ),
		];

		$this->assertEquals( $expected_columns, $orders_table->get_columns() );
	}

	/**
	 * @test
	 */
	public function it_should_match_pagination() {
		$this->prepare_tests_and_overwrite_wp_query();
		$orders_table = new Orders_Table();

		add_filter( 'edit_posts_per_page', [ $this, 'return_1' ] );

		$orders_table->prepare_items();

		remove_filter( 'edit_posts_per_page', [ $this, 'return_1' ], 10 );

		$this->set_global_value( '_REQUEST', 0, 'paged' );

		$this->assertEquals( 1, $orders_table->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( 1, $orders_table->get_pagenum() );

		$this->set_global_value( '_REQUEST', 1, 'paged' );

		$this->assertEquals( 1, $orders_table->get_pagenum() );

		$this->set_global_value( '_REQUEST', 2, 'paged' );

		$this->assertTrue( 2 === $_REQUEST['paged'] );

		$this->assertEquals( 2, $orders_table->get_pagenum() );

		$this->set_global_value( '_REQUEST', 100, 'paged' );

		$this->assertEquals( $orders_table->get_pagination_arg( 'total_items' ), $orders_table->get_pagenum() );

		$this->set_global_value( '_REQUEST', 1000, 'paged' );

		$this->assertEquals( $orders_table->get_pagination_arg( 'total_items' ), $orders_table->get_pagenum() );
	}

	/**
	 * @test
	 */
	public function it_should_match_current_action() {
		$orders_table = new Orders_Table();

		$this->set_global_value( '_REQUEST', true, 'filter_action' );
		$this->set_global_value( '_REQUEST', 'test', 'action' );

		$this->assertFalse( $orders_table->current_action() );

		$this->set_global_value( '_REQUEST', false, 'filter_action' );

		$this->assertTrue( 'test' === $orders_table->current_action() );
	}

	/**
	 * Prepare tests and overwrite the WP_Query.
	 *
	 * @return void
	 */
	protected function prepare_tests_and_overwrite_wp_query() {
		$this->prepare_test_data();

		$overwrite_query = new WP_Query( [
			'post_type' => Order::POSTTYPE,
			'posts_per_page' => 10,
			'post_status' => 'any',
			'order'       => 'ASC',
			'orderby'     => 'ID',
		] );

		$this->set_global_value( 'wp_query', $overwrite_query );
	}

	/**
	 * Prepare test data.
	 *
	 * @return array
	 */
	protected function prepare_test_data() {
		if ( ! empty( $this->orders ) ) {
			return [ $this->orders, $this->tickets, $this->event_ids ];
		}

		$this->event_ids  = $this->create_test_events();
		$this->tickets    = $this->create_test_tickets( $this->event_ids );
		$this->orders     = $this->create_test_orders( $this->tickets );

		return [ $this->orders, $this->tickets, $this->event_ids ];
	}

	/**
	 * Create test events.
	 *
	 * @param int $number_of_events
	 *
	 * @return array
	 */
	protected function create_test_events( $number_of_events = 3 ) {
		$events_ids = [];

		for ( $i = 0; $i < $number_of_events; $i ++ ) {
			$event_ts = strtotime( '2025-01-01 00:00:00' ) + $i * DAY_IN_SECONDS;
			$event_dt = new \DateTime( "@$event_ts" );

			$events_ids[] = tribe_events()->set_args(
				[
					'title'      => 'Event ' . ( $i + 1 ),
					'status'     => 'publish',
					'start_date' => $event_dt->format( 'Y-m-d H:i:s' ),
					'duration'   => ( $i + 1 ) * HOUR_IN_SECONDS,
				]
			)->create()->ID;
		}

		return $events_ids;
	}

	/**
	 * Create test tickets.
	 *
	 * @param array $event_ids
	 * @param array $number_of_tickets_per_event
	 *
	 * @return array
	 */
	protected function create_test_tickets( $event_ids, array $number_of_tickets_per_event = [ 1, 0, 2 ] ) {
		$ticket_ids = [];

		foreach ( $event_ids as $key => $event_id ) {
			for ( $i = 0; $i < $number_of_tickets_per_event[ $key ]; $i ++ ) {
				$ticket_ids[] = $this->create_tc_ticket( $event_id );
			}
		}

		return $ticket_ids;
	}

	/**
	 * Create test orders.
	 *
	 * @param array $tickets
	 * @param int   $number_of_orders_per_ticket
	 *
	 * @return array
	 */
	protected function create_test_orders( $tickets, $number_of_orders_per_ticket = 2 ) {
		$orders = [];

		$counter = 1;

		foreach ( $tickets as $ticket ) {
			for ( $i = 0; $i < $number_of_orders_per_ticket; $i ++ ) {
				$default_purchaser = [
					'purchaser_user_id'    => $counter,
					'purchaser_full_name'  => 'Test Purchaser ' . $counter,
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser ' . $counter,
					'purchaser_email'      => 'test-' . $counter . '@test.com',
				];

				$orders[] = $this->create_order( [ $ticket => 1 ], $default_purchaser );
				$counter++;
			}
		}

		return $orders;
	}

	public function return_1() {
		return 1;
	}
}
