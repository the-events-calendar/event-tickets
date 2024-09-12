<?php

namespace TEC\Tickets\Commerce\Admin_Tables;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Order;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use TEC\Tickets\Commerce\Hooks;
use WP_Screen;
use WP_Query;
use Tribe__Date_Utils as Dates;

class Orders_TableTest extends WPTestCase {
	use SnapshotAssertions;
	use Order_Maker;
	use Ticket_Maker;
	use With_Uopz;
	use With_Clock_Mock;

	/**
	 * Created orders.
	 *
	 * @var array<\WP_Post>
	 */
	protected $orders;

	/**
	 * Created tickets.
	 *
	 * @var array<int>
	 */
	protected $tickets;

	/**
	 * Created event IDs.
	 *
	 * @var array<int>
	 */
	protected $event_ids;

	/**
	 * Created user IDs.
	 *
	 * @var array<int>
	 */
	protected $user_ids = [];

	/**
	 * @before
	 */
	public function set_up_test_case() {
		global $current_screen, $typenow;
		$current_screen = WP_Screen::get( 'edit-' . Order::POSTTYPE );
		$typenow        = Order::POSTTYPE;
	}

	/**
	 * @test
	 */
	public function it_should_match_single_row() {
		$this->freeze_time( Dates::immutable( '2024-06-18 10:00:00' ) );
		$this->prepare_tests_and_overwrite_wp_query();
		$orders_table = new Orders_Table();

		$orders_table->prepare_items();

		ob_start();
		$orders_table->single_row( get_post( $this->orders['0']->ID ) );
		$html = ob_get_clean();

		$html = str_replace( $this->orders['0']->ID, '{{order_id}}', $html );

		$html = str_replace(
			$this->event_ids,
			'{{event_id}}',
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
		$this->freeze_time( Dates::immutable( '2024-06-18 10:00:00' ) );
		$this->prepare_tests_and_overwrite_wp_query();
		$orders_table = new Orders_Table();

		$orders_table->prepare_items();

		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		ob_start();

		$orders_table->display();

		$html = ob_get_clean();

		$html = str_replace(
			wp_list_pluck( $this->orders, 'ID' ),
			'{{order_id}}',
			$html
		);

		$html = str_replace(
			$this->event_ids,
			'{{event_id}}',
			$html
		);
		$html = preg_replace(
			'/#([0-9]+) - test/',
			'#{{order_id}} - test',
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
			'post_parent'      => __( 'Event', 'event-tickets' ),
			'gateway'          => __( 'Gateway', 'event-tickets' ),
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

		$_REQUEST['paged'] = 0;

		$this->assertEquals( 1, $orders_table->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( 1, $orders_table->get_pagenum() );

		$_REQUEST['paged'] = 1;

		$this->assertEquals( 1, $orders_table->get_pagenum() );

		$_REQUEST['paged'] = 2;

		$this->assertTrue( 2 === $_REQUEST['paged'] );

		$this->assertEquals( 2, $orders_table->get_pagenum() );

		$_REQUEST['paged'] = 100;

		$this->assertEquals( $orders_table->get_pagination_arg( 'total_items' ), $orders_table->get_pagenum() );

		$_REQUEST['paged'] = 1000;

		$this->assertEquals( $orders_table->get_pagination_arg( 'total_items' ), $orders_table->get_pagenum() );
	}

	/**
	 * @test
	 */
	public function it_should_match_current_action() {
		$orders_table = new Orders_Table();

		$_REQUEST['filter_action'] = true;
		$_REQUEST['action']        = 'test';

		$this->assertFalse( $orders_table->current_action() );

		$_REQUEST['filter_action'] = false;

		$this->assertTrue( 'test' === $orders_table->current_action() );
	}

	/**
	 * @test
	 */
	public function it_should_provide_results_to_ajax() {
		$this->prepare_test_data( true );

		$test_events = function ( $term ) {
			return tribe( Hooks::class )->provide_events_results_to_ajax( [], [ 'term' => $term ] );
		};

		$test_customers = function ( $term ) {
			return tribe( Hooks::class )->provide_customers_results_to_ajax( [], [ 'term' => $term ] );
		};

		$this->assertEmpty( $test_events( '' ) );
		$this->assertEmpty( $test_customers( '' ) );

		$expected_events = [
			[
				'id'   => $this->event_ids[2],
				'text' => 'Event 3',
			],
			[
				'id'   => $this->event_ids[1],
				'text' => 'Event 2',
			],
			[
				'id'   => $this->event_ids[0],
				'text' => 'Event 1',
			],
		];

		$this->assertEquals(
			[
				'results' => $expected_events,
			],
			$test_events( 'Event' )
		);

		$this->assertEquals(
			[
				'results' => [ $expected_events['1'] ],
			],
			$test_events( 'Event 2' )
		);

		$this->assertEmpty( $test_events( 'Does not Exists' ) );

		// search user by email.
		$expected_customers = [
			[
				'id' => '1',
				'text' => 'admin (admin@wordpress.test)',
			],
		];
		for ( $i = 1; $i <= 6; $i ++ ) {
			$expected_customers[] = [
				'id'   => $this->user_ids[ $i ],
				'text' => 'Test Purchaser ' . $i . ' (test-' . $i . '@test.com)',
			];
		}

		$this->assertEquals(
			[
				'results' => $expected_customers,
			],
			$test_customers( 'test' )
		);

		$this->assertEquals(
			[
				'results' => [ $expected_customers['2'] ],
			],
			$test_customers( 'Purchaser 2' )
		);

		$this->assertEquals(
			[
				'results' => [ $expected_customers['3'] ],
			],
			$test_customers( 'test-3' )
		);

		$this->assertEmpty( $test_customers( 'Does not Exists' ) );
		$this->assertEmpty( $test_customers( 'Exists' ) );
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

		global $wp_query;
		$wp_query = $overwrite_query;
	}

	/**
	 * Prepare test data.
	 *
	 * @return array
	 */
	protected function prepare_test_data( $with_wp_users = false ) {
		if ( ! empty( $this->orders ) ) {
			return [ $this->orders, $this->tickets, $this->event_ids ];
		}

		$this->event_ids  = $this->create_test_events();
		$this->tickets    = $this->create_test_tickets( $this->event_ids );
		$this->orders     = $this->create_test_orders( $this->tickets, 2 , $with_wp_users );

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
	protected function create_test_orders( $tickets, $number_of_orders_per_ticket = 2, $with_wp_users = false ) {
		$orders = [];

		$counter = 1;

		foreach ( $tickets as $ticket ) {
			for ( $i = 0; $i < $number_of_orders_per_ticket; $i ++ ) {
				if ( $with_wp_users ) {
					$user_id = wp_insert_user( [
						'user_pass'    => 'TEST_PASS_' . $counter,
						'user_login'   => 'test_user_' . $counter,
						'user_email'   => 'test-' . $counter . '@test.com',
						'display_name' => 'Test Purchaser ' . $counter,
						'first_name'   => 'Test',
						'last_name'    => 'Purchaser ' . $counter,
						'role'         => 'contributor',
					] );
				}

				$default_purchaser = [
					'purchaser_user_id'    => ! $with_wp_users || is_wp_error( $user_id ) ? $counter : $user_id,
					'purchaser_full_name'  => 'Test Purchaser ' . $counter,
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser ' . $counter,
					'purchaser_email'      => 'test-' . $counter . '@test.com',
				];

				if ( $with_wp_users && ! is_wp_error( $user_id ) ) {
					$this->user_ids[ $counter ] = $user_id;
				}

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
