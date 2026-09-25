<?php

namespace TEC\Tickets\Commerce\Order_Items;

use Closure;
use Generator;
use RuntimeException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items as Order_Items_Repository;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Ticket;
use TEC\Tickets\Commerce\Traits\Type;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Coupon_Creator;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Post;

class Reader_Test extends Controller_Test_Case {
	use Coupon_Creator;
	use Fee_Creator;
	use Order_Maker;
	use Ticket_Maker;
	use Type;
	use With_Uopz;

	protected string $controller_class = Controller::class;

	protected array $sub_controller_classes = [ Writer::class, Reader::class ];

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Order_Items_Table() )->empty_table();
	}

	public function cart_provider(): Generator {
		yield 'tickets only' => [
			fn( array $ticket_ids ) => [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ],
		];

		yield 'fee' => [
			function ( array $ticket_ids ) {
				$this->create_fee_for_ticket( $ticket_ids[0], [ 'raw_amount' => 2.5 ] );

				return [ $ticket_ids[0] => 2, $ticket_ids[1] => 1 ];
			},
		];

		yield 'coupon' => [
			function ( array $ticket_ids ) {
				$coupon = $this->create_coupon( [ 'sub_type' => 'flat', 'raw_amount' => 5 ] );

				return [
					$ticket_ids[0] => 2,
					$coupon->id    => [
						'id'       => $this->get_unique_type_id( $coupon->id, 'coupon' ),
						'quantity' => 1,
						'extras'   => [ 'type' => 'coupon' ],
					],
				];
			},
		];

		yield 'sale price' => [
			function ( array $ticket_ids ) {
				update_post_meta( $ticket_ids[1], Ticket::$sale_price_checked_key, '1' );
				update_post_meta( $ticket_ids[1], Ticket::$sale_price_key, '17.99' );

				return [ $ticket_ids[1] => 3 ];
			},
		];

		/*
		 * Event Tickets Plus cannot run here, so the discount line it adds at checkout is added the same way, through
		 * the filter it uses, and goes through the real order creation and writer.
		 */
		yield 'purchase rule discount' => [
			function ( array $ticket_ids ) {
				$discount = ( include codecept_data_dir( 'order-items/discount.php' ) )[1];
				add_filter(
					'tec_tickets_commerce_create_order_from_cart_items',
					static function ( $items ) use ( $discount ) {
						$items[] = $discount;

						return $items;
					},
					20
				);

				return [ $ticket_ids[0] => 3 ];
			},
		];
	}

	/**
	 * @dataProvider cart_provider
	 */
	public function test_an_order_loads_the_same_from_the_table_as_from_its_meta( Closure $make_cart ): void {
		[ , $ticket_ids ] = $this->make_tickets();
		$cart             = $make_cart->call( $this, $ticket_ids );
		$off              = $this->create_order( $cart );
		$this->register_controller( true );

		$on = $this->create_order( $cart );

		$this->assertSame( '2', get_post_meta( $on->ID, Writer::VERSION_META_KEY, true ) );
		$this->assertCount( count( get_post_meta( $on->ID, Order::$items_meta_key, true ) ), tribe( Order_Items_Repository::class )->get_by_order( $on->ID ) );
		// Without its meta copy, the order can only load its items from the table.
		delete_post_meta( $on->ID, Order::$items_meta_key );
		$expected = $this->load( $off->ID );
		$actual   = $this->load( $on->ID );
		$this->assertNotEmpty( $expected->items );
		foreach ( [ 'items', 'fees', 'coupons' ] as $property ) {
			$this->assertEquals( $expected->{$property} ?? null, $actual->{$property} ?? null, $property );
			// serialize() is as strict as === on keys, key order and scalar types, but compares objects by state.
			$this->assertSame( serialize( $expected->{$property} ?? null ), serialize( $actual->{$property} ?? null ), $property );
		}
	}

	public function middle_insert_provider(): Generator {
		yield 'ticket between two tickets' => [
			function ( int $event_id, int $vip, int $ga ) {
				$new   = $this->create_tc_ticket( $event_id, 30 );
				$order = $this->create_order( [ $vip => 1, $ga => 2 ], [ 'order_status' => Pending::SLUG ] );
				$items = array_values( get_post_meta( $order->ID, Order::$items_meta_key, true ) );

				return [ $order->ID, [ $items[0], array_merge( $items[0], [ 'ticket_id' => $new ] ), $items[1] ], [ $vip, $new, $ga ] ];
			},
		];

		yield 'ticket before a fee' => [
			function ( int $event_id, int $vip, int $ga ) {
				$this->create_fee_for_ticket( $vip, [ 'raw_amount' => 2.5 ] );
				$order = $this->create_order( [ $vip => 1 ], [ 'order_status' => Pending::SLUG ] );
				$items = array_values( get_post_meta( $order->ID, Order::$items_meta_key, true ) );

				return [ $order->ID, [ $items[0], array_merge( $items[0], [ 'ticket_id' => $ga ] ), $items[1] ], [ $vip, $ga, $vip ] ];
			},
		];
	}

	/**
	 * @dataProvider middle_insert_provider
	 */
	public function test_a_line_added_in_the_middle_of_an_order_loads_in_its_saved_place( Closure $make_order ): void {
		$this->register_controller( true );
		[ $event_id, [ $vip, $ga ] ]         = $this->make_tickets();
		[ $order_id, $items, $ticket_order ] = $make_order->call( $this, $event_id, $vip, $ga );
		tec_tc_orders()->by_args( [ 'id' => $order_id, 'status' => 'any' ] )->set_args( [ 'items' => $items ] )->save();
		$read = null;
		// Fees leave the items list after the reader runs, so the list is checked as the reader returns it.
		add_filter(
			'tec_tickets_commerce_order_model_items',
			static function ( $items ) use ( &$read ) {
				$read = $items;

				return $items;
			},
			20
		);

		$this->load( $order_id );

		$this->assertSame( '2', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );
		$this->assertSame( [ 0, 1, 2 ], array_keys( $read ) );
		$this->assertEquals( $ticket_order, array_column( $read, 'ticket_id' ) );
		$this->assertSame( serialize( get_post_meta( $order_id, Order::$items_meta_key, true ) ), serialize( $read ) );
	}

	public function test_an_old_order_loads_without_querying_the_table(): void {
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1 ] );
		$this->register_controller( true );
		$queries = $this->count_queries_against_the_table();

		$loaded = $this->load( $order->ID );

		$this->assertSame( 0, $queries() );
		$this->assertEquals( get_post_meta( $order->ID, Order::$items_meta_key, true ), $loaded->items );
	}

	public function test_a_version_2_order_loads_from_the_table_in_one_query(): void {
		$this->register_controller( true );
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] );
		$items            = get_post_meta( $order->ID, Order::$items_meta_key, true );
		$this->change_first_row_quantity( $order->ID, 7 );
		$queries = $this->count_queries_against_the_table();

		$loaded = $this->load( $order->ID );

		$this->assertSame( 1, $queries() );
		$items[ array_key_first( $items ) ]['quantity'] = 7;
		$this->assertSame( serialize( $items ), serialize( $loaded->items ) );
	}

	public function test_an_order_unmarked_by_a_failed_sync_loads_its_new_items_from_the_meta(): void {
		$this->register_controller( true );
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1 ] );
		$items            = get_post_meta( $order->ID, Order::$items_meta_key, true );
		$items[ array_key_first( $items ) ]['quantity'] = 4;
		// The state a failed sync leaves: the new items in the meta, the stale rows in the table, and no version.
		update_post_meta( $order->ID, Order::$items_meta_key, $items );
		delete_post_meta( $order->ID, Writer::VERSION_META_KEY );
		$queries = $this->count_queries_against_the_table();

		$loaded = $this->load( $order->ID );

		$this->assertSame( 0, $queries() );
		$this->assertSame( $items, $loaded->items );
	}

	public function test_a_version_2_order_without_rows_loads_from_the_meta(): void {
		$this->register_controller( true );
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1 ] );
		$this->empty_table();

		$loaded = $this->load( $order->ID );

		$this->assertSame( '2', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
		$this->assertSame( get_post_meta( $order->ID, Order::$items_meta_key, true ), $loaded->items );
	}

	public function test_a_failed_read_loads_from_the_meta(): void {
		$this->register_controller( true );
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1 ] );
		$this->set_class_fn_return(
			Order_Items_Repository::class,
			'get_by_order',
			static function () {
				throw new RuntimeException( 'Query failed.' );
			},
			true
		);

		$loaded = $this->load( $order->ID );

		$this->assertSame( get_post_meta( $order->ID, Order::$items_meta_key, true ), $loaded->items );
		$this->assert_logged( 'debug', 'could not be read' );
	}

	public function disabled_provider(): Generator {
		yield 'constant' => [
			function () {
				$this->set_const_value( Controller::DISABLED, true );
			},
		];

		yield 'filter' => [
			function () {
				add_filter( 'tec_tickets_commerce_order_items_active', '__return_false' );
			},
		];
	}

	/**
	 * @dataProvider disabled_provider
	 */
	public function test_with_the_switch_off_a_version_2_order_loads_from_the_meta( Closure $switch_off ): void {
		$switch_off->call( $this );
		$this->make_controller()->register();
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1 ] );
		$items            = get_post_meta( $order->ID, Order::$items_meta_key, true );
		// Stores the order the way it would have been with the switch on.
		tribe( Writer::class )->write_created_order( $order->ID, $items );
		$this->assertSame( '2', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
		$this->change_first_row_quantity( $order->ID, 7 );
		$queries = $this->count_queries_against_the_table();

		$loaded = $this->load( $order->ID );

		$this->assertSame( 0, $queries() );
		$this->assertSame( $items, $loaded->items );
	}

	/**
	 * Loads an order past both caches of the order model, which a meta or table write does not invalidate.
	 */
	private function load( int $order_id ): WP_Post {
		wp_cache_flush();

		return tec_tc_get_order( $order_id );
	}

	private function change_first_row_quantity( int $order_id, int $quantity ): void {
		$row = tribe( Order_Items_Repository::class )->get_by_order( $order_id )[0];
		DB::update( Order_Items_Table::table_name(), [ 'quantity' => $quantity ], [ 'id' => $row->toArray()['id'] ] );
	}

	private function register_controller( bool $active ): void {
		add_filter( 'tec_tickets_commerce_order_items_active', $active ? '__return_true' : '__return_false' );
		$this->make_controller()->register();
	}

	/**
	 * @return array{0: int, 1: int[]} The event ID and two ticket IDs.
	 */
	private function make_tickets(): array {
		$event_id = static::factory()->post->create( [ 'post_type' => 'page' ] );

		return [ $event_id, [ $this->create_tc_ticket( $event_id, 10 ), $this->create_tc_ticket( $event_id, 20 ) ] ];
	}

	private function count_queries_against_the_table(): callable {
		$count = 0;
		$table = Order_Items_Table::table_name();

		add_filter(
			'query',
			static function ( $query ) use ( &$count, $table ) {
				if ( false !== strpos( $query, "`{$table}`" ) ) {
					$count++;
				}

				return $query;
			}
		);

		return static function () use ( &$count ): int {
			return $count;
		};
	}
}
