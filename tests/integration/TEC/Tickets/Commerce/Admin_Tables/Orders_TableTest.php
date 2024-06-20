<?php

namespace TEC\Tickets\Commerce\Admin_Tables;

use TEC\Tickets\Commerce\Order;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use PHPUnit\Framework\Assert;
use WP_Screen;
use WP_Query;

class Orders_TableTest extends \Codeception\TestCase\WPTestCase {

	use SnapshotAssertions;
	use Order_Maker;
	use Ticket_Maker;
	use With_Uopz;

	/**
	 * Store global modifications.
	 *
	 * @var mixed
	 */
	protected static $modified_globals = [];

	/**
	 * Store global modifications.
	 *
	 * @var mixed
	 */
	protected static $modified_super_globals = [];

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
	 * @after
	 */
	public function restore_global() {
		foreach ( self::$modified_globals as $restore_global_callback ) {
			$restore_global_callback();
		}

		foreach ( self::$modified_super_globals as $restore_super_global_callback ) {
			$restore_super_global_callback();
		}

		self::$modified_globals = [];

		self::$modified_super_globals = [];
	}

	/**
	 * Set a super global value.
	 *
	 * @param string $super_global
	 * @param array  $values
	 */
	private function set_super_global_offsets( &$super_global, array $values ) {
		foreach ( $values as $key => $value ) {
			$previous_value = empty( $super_global[ $key ] ) ? null : $super_global[ $key ];
			if ( null === $previous_value ) {
				$restore_callback = static function () use ( $super_global, $key ) {
					$super_global[ $key ] = null;
					Assert::assertTrue( empty( $super_global[ $key ] ) );
				};
			} else {
				$restore_callback = static function () use ( $previous_value, $super_global, $key ) {
					$super_global[ $key ] = $previous_value;
					Assert::assertEquals( $previous_value, $super_global[ $key ] );
				};
			}

			$super_global[ $key ] = $value;

			$this->assertTrue( $value === $super_global[ $key ] );

			self::$modified_super_globals[] = $restore_callback;
		}
	}

	/**
	 * Set a global value.
	 *
	 * @param string $const
	 * @param mixed  $value
	 * @param int    $offset
	 */
	private function set_global_value( $global, $value, $offset = '' ) {
		$previous_value     = empty( $GLOBALS[ $global ] ) ? null : $GLOBALS[ $global ];
		// force set the $global offset.
		$GLOBALS[ $global ] = $previous_value;
		$previous_value     = $offset && ! empty( $previous_value[ $offset ] ) ? $previous_value[ $offset ] : $previous_value;

		if ( null === $previous_value ) {
			$restore_callback = static function () use ( $global, $offset ) {
				if ( $offset ) {
					$GLOBALS[ $global ][ $offset ] = null;
				} else {
					$GLOBALS[ $global ] = null;
				}
				Assert::assertTrue( $offset ? empty( $GLOBALS[ $global ][ $offset ] ) : empty( $GLOBALS[ $global ] ) );
			};
		} else {
			$restore_callback = static function () use ( $previous_value, $global, $offset ) {
				if ( $offset ) {
					$GLOBALS[ $global ][ $offset ] = $previous_value;
				} else {
					$GLOBALS[ $global ] = $previous_value;
				}
				Assert::assertEquals( $previous_value, $offset ? $GLOBALS[ $global ][ $offset ] : $GLOBALS[ $global ] );
			};
		}

		if ( $offset ) {
			$GLOBALS[ $global ][ $offset ] = $value;
		} else {
			$GLOBALS[ $global ] = $value;
		}

		self::$modified_globals[] = $restore_callback;
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

		add_filter( 'edit_posts_per_page', function () { return 1; } );

		$orders_table->prepare_items();

		$this->set_super_global_offsets( $_REQUEST, [ 'paged' => 0 ] );

		$this->assertEquals( 1, $orders_table->get_pagination_arg( 'per_page' ) );

		$this->assertEquals( 1, $orders_table->get_pagenum() );

		$this->set_super_global_offsets( $_REQUEST, [ 'paged' => 1 ] );

		$this->assertEquals( 1, $orders_table->get_pagenum() );

		$this->set_super_global_offsets( $_REQUEST, [ 'paged' => 2 ] );

		$this->assertTrue( 2 === $_REQUEST['paged'] );

		$this->assertEquals( 2, $orders_table->get_pagenum() );

		$this->set_super_global_offsets( $_REQUEST, [ 'paged' => 100 ] );

		$this->assertEquals( $orders_table->get_pagination_arg( 'total_items' ), $orders_table->get_pagenum() );

		$this->set_super_global_offsets( $_REQUEST, [ 'paged' => 1000 ] );

		$this->assertEquals( $orders_table->get_pagination_arg( 'total_items' ), $orders_table->get_pagenum() );
	}

	/**
	 * @test
	 */
	public function it_should_match_current_action() {
		$orders_table = new Orders_Table();

		$this->set_super_global_offsets( $_REQUEST, [ 'filter_action' => true ] );
		$this->set_super_global_offsets( $_REQUEST, [ 'action' => 'test' ] );

		$this->assertFalse( $orders_table->current_action() );

		$this->set_super_global_offsets( $_REQUEST, [ 'filter_action' => false ] );

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
}
