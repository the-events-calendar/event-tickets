<?php

namespace TEC\Tickets\Admin\Tickets;

use TEC\Tickets\Admin\Tickets\List_Table;
use TEC\Tickets\Commerce as TicketsCommerce;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

/**
 * Tests for the List_Table class.
 */
class List_TableTest extends \Codeception\TestCase\WPTestCase {
	use SnapshotAssertions;
	use Ticket_Maker;

	/**
	 * @var List_Table
	 */
	protected $list_table;

	/**
	 * Created ticket IDs.
	 *
	 * @var array
	 */
	protected $ticket_ids;

	/**
	 * Created event IDs.
	 *
	 * @var array
	 */
	protected $event_ids;

	public function setUp(): void {
		// before
		parent::setUp();

		add_filter( 'tec_tickets_admin_tickets_table_provider_info', function() {
			return [
				TicketsCommerce\Module::class => [
					'title'              => 'Tickets Commerce',
					'event_meta_key'     => TicketsCommerce\Attendee::$event_relation_meta_key,
					'attendee_post_type' => TicketsCommerce\Attendee::POSTTYPE,
					'ticket_post_type'   => TicketsCommerce\Ticket::POSTTYPE,
				]
			];
		} );

		$this->prepare_test_data();
		$this->list_table = new List_Table();
	}

	public function tearDown(): void {
		// Delete the test data.
		foreach ( $this->ticket_ids as $id ) {
			wp_delete_post( $id, true );
		}
		foreach ( $this->event_ids as $id ) {
			wp_delete_post( $id, true );
		}

		remove_filter( 'tec_tickets_admin_tickets_table_provider_options', [ $this, 'add_tc_ticket_type' ] );

		// then
		parent::tearDown();
	}

	public function add_tc_ticket_type( $providers ) {
		$providers['tec_tc_ticket'] = 'Tickets Commerce';

		return $providers;
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

		$events_ids[] = tribe_events()->set_args(
			[
				'title'      => 'Searchable Event ' . ( $i + 1 ),
				'status'     => 'publish',
				'start_date' => $event_dt->format( 'Y-m-d H:i:s' ),
				'duration'   => ( $i + 1 ) * HOUR_IN_SECONDS,
			]
		)->create()->ID;

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
	protected function create_test_tickets( $event_ids, array $number_of_tickets_per_event = [ 1, 0, 2, 1 ] ) {
		$ticket_ids = [];
		$override_index = 0;

		foreach ( $event_ids as $key => $event_id ) {
			for ( $i = 0; $i < $number_of_tickets_per_event[ $key ]; $i ++ ) {
				$overrides = $this->get_ticket_overrides( $override_index );

				$test_ticket = $this->create_tc_ticket( $event_id, $overrides['ticket_price'], $overrides );
				$ticket_ids[] = $test_ticket;
				$override_index++;
			}
		}

		return $ticket_ids;
	}

	/**
	 * Tickets overrides for sorting tests.
	 *
	 * @param int $index
	 *
	 * @return array
	 */
	protected function get_ticket_overrides( $index ) {
		$overrides = [
			[
				'ticket_name'             => "AAA Ticket {$index}",
				'ticket_description'      => "AAA Ticket description {$index}",
				'ticket_price'            => 50,
				'ticket_start_date'       => '2020-01-02',
				'ticket_start_time'       => '08:00:00',
				'ticket_end_date'         => '2050-03-01',
				'ticket_end_time'         => '20:00:00',
				'ticket_sku'              => "TEST-TKT-{$index}",
			],
			[
				'ticket_name'             => "Searchable Ticket {$index}",
				'ticket_description'      => "Searchable Ticket description {$index}",
				'ticket_price'            => 5,
				'ticket_start_date'       => '2040-01-02',
				'ticket_start_time'       => '08:00:00',
				'ticket_end_date'         => '2090-03-01',
				'ticket_end_time'         => '20:00:00',
				'ticket_sku'              => "TEST-TKT-{$index}",
			],
			[
				'ticket_name'             => "BBB Ticket {$index}",
				'ticket_description'      => "AAA Ticket description {$index}",
				'ticket_price'            => 100,
				'ticket_start_date'       => '2030-01-02',
				'ticket_start_time'       => '08:00:00',
				'ticket_end_date'         => '2040-03-01',
				'ticket_end_time'         => '20:00:00',
				'ticket_sku'              => "TEST-TKT-{$index}",
			],
			[
				'ticket_name'             => "CCC Ticket {$index}",
				'ticket_description'      => "AAA Ticket description {$index}",
				'ticket_price'            => 25,
				'ticket_start_date'       => '2010-01-02',
				'ticket_start_time'       => '08:00:00',
				'ticket_end_date'         => '2060-03-01',
				'ticket_end_time'         => '20:00:00',
				'ticket_sku'              => "TEST-TKT-{$index}",
			],
		];

		return $overrides[ $index ];
	}

	/**
	 * Prepare test data.
	 *
	 * @return array
	 */
	protected function prepare_test_data() {
		if ( ! empty( $this->ticket_ids ) ) {
			return [ $this->ticket_ids, $this->event_ids ];
		}

		$this->event_ids  = $this->create_test_events();
		$this->ticket_ids = $this->create_test_tickets( $this->event_ids );

		return [ $this->ticket_ids, $this->event_ids ];
	}

	// test
	public function test_construct() {
		$this->assertInstanceOf( List_Table::class, $this->list_table );
	}

	// test
	public function test_prepare_items() {
		$_GET['status-filter'] = 'all';
		$_GET['provider-filter'] = addslashes( TicketsCommerce\Module::class );
		$this->list_table->prepare_items();

		$this->assertNotEmpty( $this->list_table->items );
		$this->assertEquals( count( $this->ticket_ids ), count( $this->list_table->items ) );
	}

	// test
	public function test_get_columns() {
		$columns = $this->list_table->get_columns();
		$this->assertArrayHasKey( 'name', $columns );
		$this->assertArrayHasKey( 'id', $columns );
		$this->assertArrayHasKey( 'event', $columns );
		$this->assertArrayHasKey( 'start', $columns );
		$this->assertArrayHasKey( 'end', $columns );
		$this->assertArrayHasKey( 'days_left', $columns );
		$this->assertArrayHasKey( 'price', $columns );
		$this->assertArrayHasKey( 'sold', $columns );
		$this->assertArrayHasKey( 'remaining', $columns );
		$this->assertArrayHasKey( 'sales', $columns );
	}

	// test
	public function test_get_sortable_columns() {
		$sortable_columns = $this->list_table->get_sortable_columns();
		$this->assertArrayHasKey( 'name', $sortable_columns );
		$this->assertArrayHasKey( 'id', $sortable_columns );
		$this->assertArrayNotHasKey( 'event', $sortable_columns );
		$this->assertArrayHasKey( 'start', $sortable_columns );
		$this->assertArrayHasKey( 'end', $sortable_columns );
		$this->assertArrayHasKey( 'days_left', $sortable_columns );
		$this->assertArrayHasKey( 'price', $sortable_columns );
		$this->assertArrayHasKey( 'sold', $sortable_columns );
		$this->assertArrayNotHasKey( 'remaining', $sortable_columns );
		$this->assertArrayNotHasKey( 'sales', $sortable_columns );
	}

	// test
	public function test_get_default_hidden_columns() {
		$default_hidden_columns = $this->list_table->get_default_hidden_columns();
		$this->assertNotContains( 'name', $default_hidden_columns );
		$this->assertContains( 'id', $default_hidden_columns );
		$this->assertNotContains( 'event', $default_hidden_columns );
		$this->assertContains( 'start', $default_hidden_columns );
		$this->assertNotContains( 'end', $default_hidden_columns );
		$this->assertContains( 'days_left', $default_hidden_columns );
		$this->assertNotContains( 'price', $default_hidden_columns );
		$this->assertNotContains( 'sold', $default_hidden_columns );
		$this->assertNotContains( 'remaining', $default_hidden_columns );
		$this->assertContains( 'sales', $default_hidden_columns );
	}

	/**
	 * @test
	 * @dataProvider sorting_columns_provider
	 */
	public function test_sorting( $column ) {
		$_GET['status-filter'] = 'all';
		$_GET['provider-filter'] = addslashes( TicketsCommerce\Module::class );
		$_GET['orderby'] = $column;
		$_GET['order'] = 'asc';
		$this->list_table->prepare_items();
		$json_string = json_encode( $this->list_table->items, JSON_PRETTY_PRINT );
		$json_string = str_replace( $this->ticket_ids, [ '1', '2', '3', '4' ], $json_string );
		$this->assertMatchesJsonSnapshot( $json_string );
	}

	/**
	 * Data provider for testing different scenarios of get_gateway_dashboard_url_by_order.
	 *
	 * @return array
	 */
	public function sorting_columns_provider() {
			yield "sorting by name"      => [ 'name' ];
			yield "sorting by id"        => [ 'id' ];
			yield "sorting by start"     => [ 'start' ];
			yield "sorting by end"       => [ 'end' ];
			yield "sorting by days_left" => [ 'days_left' ];
			yield "sorting by price"     => [ 'price' ];
	}

	/**
	 * @test
	 * @dataProvider search_provider
	 */
	public function test_search( $term ) {
		$_GET['status-filter'] = 'all';
		$_GET['provider-filter'] = addslashes( TicketsCommerce\Module::class );
		$_GET['s'] = $term;
		$this->list_table->prepare_items();
		$json_string = json_encode( $this->list_table->items, JSON_PRETTY_PRINT );
		$json_string = str_replace( $this->ticket_ids, [ '1', '2', '3', '4' ], $json_string );
		$this->assertMatchesJsonSnapshot( $json_string );
	}

	/**
	 * Data provider for testing search terms.
	 *
	 * @return array
	 */
	public function search_provider() {
			yield "search existing items"     => [ 'searchable' ];
			yield "search non-existent items" => [ 'noexist' ];
	}

	/**
	 * @test
	 * @dataProvider filter_provider
	 */
	public function test_filters( $filter ) {
		$_GET['status-filter'] = $filter;
		$_GET['provider-filter'] = addslashes( TicketsCommerce\Module::class );
		$this->list_table->prepare_items();
		$json_string = json_encode( $this->list_table->items, JSON_PRETTY_PRINT );
		$json_string = str_replace( $this->ticket_ids, [ '1', '2', '3', '4' ], $json_string );
		$this->assertMatchesJsonSnapshot( $json_string );
	}

	/**
	 * Data provider for testing search terms.
	 *
	 * @return array
	 */
	public function filter_provider() {
			yield "filter by active"     => [ 'active' ];
			yield "filter by past"       => [ 'past' ];
			yield "filter by upcoming"   => [ 'upcoming' ];
			yield "filter by discounted" => [ 'discounted' ];
			yield "filter by all"        => [ 'all' ];
	}
}


