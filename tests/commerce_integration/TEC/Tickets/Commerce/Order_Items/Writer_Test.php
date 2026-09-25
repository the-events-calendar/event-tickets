<?php

namespace TEC\Tickets\Commerce\Order_Items;

use ArrayObject;
use Closure;
use DateTimeInterface;
use Error;
use Generator;
use RuntimeException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Gateways\Manual\Order as Manual_Order;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Line_Items\Line_Item_Types;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items as Order_Items_Repository;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\OrderModifiers\Fee_Creator;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Post;

class Writer_Test extends Controller_Test_Case {
	use Fee_Creator;
	use Order_Maker;
	use Ticket_Maker;
	use With_Uopz;

	protected string $controller_class = Controller::class;

	protected array $sub_controller_classes = [ Writer::class ];

	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Order_Items_Table() )->empty_table();
	}

	public function switch_provider(): Generator {
		yield 'switch on' => [ true ];
		yield 'switch off' => [ false ];
	}

	/**
	 * @dataProvider switch_provider
	 */
	public function test_order_created_fires_once_after_the_order_metas_are_saved( bool $active ): void {
		$this->register_controller( $active );
		[ $event_id, $ticket_ids ] = $this->make_tickets();
		$calls                     = [];
		add_action(
			'tec_tickets_commerce_order_created',
			static function ( $order_id, $items ) use ( &$calls ) {
				$calls[] = [
					$order_id,
					$items,
					get_post_meta( $order_id, Order::$events_in_order_meta_key ),
					get_post_meta( $order_id, Order::$tickets_in_order_meta_key ),
				];
			},
			10,
			2
		);

		$order = $this->create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] );

		$this->assertCount( 1, $calls );
		[ $order_id, $items, $events, $tickets ] = $calls[0];
		$this->assertSame( $order->ID, $order_id );
		$this->assertSame( get_post_meta( $order->ID, Order::$items_meta_key, true ), $items );
		$this->assertEquals( [ $event_id ], $events );
		$this->assertEqualsCanonicalizing( $ticket_ids, $tickets );
	}

	public function gateway_provider(): Generator {
		yield 'Stripe' => [ fn( array $cart ) => $this->create_order_through_stripe( $cart ) ];
		yield 'PayPal' => [ fn( array $cart ) => $this->create_order( $cart ) ];
		yield 'Square' => [ fn( array $cart ) => $this->create_order_through_square( $cart ) ];
		yield 'Free' => [ fn( array $cart ) => $this->create_order( $cart, [ 'gateway' => tribe( Free_Gateway::class ) ] ) ];
		yield 'Manual' => [
			function ( array $cart ) {
				$items = [];
				foreach ( $cart as $ticket_id => $quantity ) {
					$items[ $ticket_id ] = [
						'ticket_id' => $ticket_id,
						'quantity'  => $quantity,
						'extra'     => [ 'optout' => false ],
					];
				}

				return tribe( Manual_Order::class )->create( $items );
			},
		];
	}

	/**
	 * @dataProvider gateway_provider
	 */
	public function test_it_writes_one_row_per_item_in_one_query_and_marks_the_order( Closure $create_order ): void {
		$this->register_controller( true );
		[ , $ticket_ids ] = $this->make_tickets();
		$given_items      = null;
		add_action(
			'tec_tickets_commerce_order_created',
			static function ( $order_id, $items ) use ( &$given_items ) {
				$given_items = $items;
			},
			5,
			2
		);
		$queries = $this->count_queries_against_the_table();

		$order = $create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] );

		$this->assertInstanceOf( WP_Post::class, $order );
		$this->assertSame( [ 'DELETE' => 1, 'INSERT' => 1 ], $queries() );
		$rows = tribe( Order_Items_Repository::class )->get_by_order( $order->ID );
		$this->assertCount( count( $given_items ), $rows );
		$rows = array_map( static fn( $row ) => $row->toArray(), $rows );
		$this->assertSame( array_map( 'strval', array_keys( $given_items ) ), array_column( $rows, 'item_key' ) );
		$this->assertSame( range( 0, count( $given_items ) - 1 ), array_column( $rows, 'position' ) );
		$this->assertSame( '2', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
		$this->assertSame( $given_items, get_post_meta( $order->ID, Order::$items_meta_key, true ) );
	}

	public function test_it_replaces_the_rows_left_under_a_recycled_order_id(): void {
		$this->register_controller( true );
		[ , $ticket_ids ] = $this->make_tickets();
		$gone             = $this->create_order( [ $ticket_ids[0] => 1 ] );
		// Truncating the posts table drops the post and keeps its rows, and the next post can take its ID.
		DB::query( DB::prepare( 'DELETE FROM %i WHERE ID = %d', DB::prefix( 'posts' ), $gone->ID ) );
		$items = get_post_meta( $this->create_order( [ $ticket_ids[1] => 2 ] )->ID, Order::$items_meta_key, true );

		do_action( 'tec_tickets_commerce_order_created', $gone->ID, $items );

		$rows = tribe( Order_Items_Repository::class )->get_by_order( $gone->ID );
		$this->assertSame( [ $ticket_ids[1] ], array_map( static fn( $row ) => $row->toArray()['ticket_id'], $rows ) );
	}

	public function test_it_writes_nothing_for_an_order_without_items(): void {
		$this->register_controller( true );

		$order_id = tec_tc_orders()->set_args( [ 'title' => 'No items', 'gateway' => 'manual' ] )->create()->ID;

		$this->assertSame( [], tribe( Order_Items_Repository::class )->get_by_order( $order_id ) );
		$this->assertSame( '', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );
	}

	public function test_nothing_fires_or_is_written_when_the_order_post_is_not_inserted(): void {
		$this->register_controller( true );
		$fired = 0;
		add_action( 'tec_tickets_commerce_order_created', static function () use ( &$fired ) {
			$fired++;
		} );
		// wp_insert_post() returns 0, not a WP_Error, when the insert is refused.
		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		tec_tc_orders()->set_args(
			[
				'title'   => 'Refused',
				'gateway' => 'manual',
				'items'   => [ [ 'ticket_id' => 7, 'type' => 'ticket', 'quantity' => 1, 'price' => 10.0, 'sub_total' => 10.0 ] ],
			]
		)->create();

		$this->assertSame( 0, $fired );
		$this->assertSame( 0, Order_Items_Table::get_total_items() );
	}

	public function failed_write_provider(): Generator {
		yield 'duplicate line' => [
			function () {
				add_filter(
					'tec_tickets_commerce_create_order_from_cart_items',
					static function ( $items ) {
						$items[] = reset( $items );

						return $items;
					}
				);
			},
		];

		yield 'insert fails' => [
			function () {
				$this->set_class_fn_return(
					Order_Items_Repository::class,
					'insert_many',
					static function () {
						throw new RuntimeException( 'Insert failed.' );
					},
					true
				);
			},
		];

		yield 'unsupported object in an item' => [
			function () {
				add_filter(
					'tec_tickets_commerce_create_order_from_cart_items',
					static function ( $items ) {
						$items[ array_key_first( $items ) ]['extra']['object'] = new ArrayObject( [ 1 ] );

						return $items;
					}
				);
			},
		];
	}

	/**
	 * @dataProvider failed_write_provider
	 */
	public function test_a_failed_write_leaves_the_order_stored_the_old_way( Closure $break_the_write ): void {
		$this->register_controller( true );
		[ $event_id, $ticket_ids ] = $this->make_tickets();
		$break_the_write->call( $this );

		$order = $this->create_order( [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ] );

		$this->assertInstanceOf( WP_Post::class, $order );
		$this->assertSame( 0, Order_Items_Table::get_total_items() );
		$this->assertSame( '', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
		$this->assertNotEmpty( get_post_meta( $order->ID, Order::$items_meta_key, true ) );
		$this->assertSame( tribe( Completed::class )->get_wp_slug(), get_post_status( $order->ID ) );
		$this->assert_logged( 'debug', 'could not be written' );
	}

	public function test_it_does_not_commit_a_transaction_open_around_the_order_creation(): void {
		$this->register_controller( true );
		[ $event_id, $ticket_ids ] = $this->make_tickets();
		$order_ids                 = [];
		add_action(
			'tec_tickets_commerce_order_created',
			static function ( $order_id ) use ( &$order_ids ) {
				$order_ids[] = $order_id;
			}
		);
		tribe( Cart::class )->get_repository()->upsert_item( $ticket_ids[1], 2 );

		tec_tickets_tests_fake_transactions_disable();

		try {
			// Square wraps the order creation in its own transaction and rolls it back on a duplicate order.
			DB::beginTransaction();
			$this->create_order_without_transitions();
			DB::rollback();

			$this->assertCount( 1, $order_ids );
			$this->assertNull( DB::get_var( DB::prepare( 'SELECT ID FROM %i WHERE ID = %d', DB::prefix( 'posts' ), $order_ids[0] ) ) );
			$this->assertSame( 0, Order_Items_Table::get_total_items() );
		} finally {
			tec_tickets_tests_fake_transactions_enable();
			tribe( Cart::class )->clear_cart();
			foreach ( array_merge( $order_ids, [ $event_id ], $ticket_ids ) as $post_id ) {
				wp_delete_post( $post_id, true );
			}
			$this->empty_table();
			// Beginning the outer transaction committed the test's own: persist the clean up.
			DB::query( 'COMMIT' );
		}
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
	public function test_it_writes_nothing_with_the_switch_off( Closure $switch_off ): void {
		$switch_off->call( $this );
		$this->make_controller()->register();
		[ , $ticket_ids ] = $this->make_tickets();

		$order = $this->create_order( [ $ticket_ids[0] => 1 ] );

		$this->assertSame( 0, Order_Items_Table::get_total_items() );
		$this->assertSame( '', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
	}

	public function test_order_metas_match_those_of_a_switch_off_order_for_the_same_cart(): void {
		[ , $ticket_ids ] = $this->make_tickets();
		$cart             = [ $ticket_ids[0] => 1, $ticket_ids[1] => 2 ];
		$off              = $this->create_order( $cart );
		$this->register_controller( true );

		$on = $this->create_order( $cart );

		$this->assertSame( '2', get_post_meta( $on->ID, Writer::VERSION_META_KEY, true ) );
		foreach ( [ Order::$items_meta_key, Order::$events_in_order_meta_key, Order::$tickets_in_order_meta_key ] as $meta_key ) {
			$this->assertEquals( get_post_meta( $off->ID, $meta_key ), get_post_meta( $on->ID, $meta_key ), $meta_key );
		}
	}

	public function test_a_checkout_that_drops_a_middle_line_keeps_the_other_rows(): void {
		$this->register_controller( true );
		[ $event_id, [ $vip, $ga ] ] = $this->make_tickets();
		add_filter(
			'tec_tickets_commerce_create_order_from_cart_items',
			static function ( $items ) use ( $vip ) {
				// The fee filter renumbers the list the same way.
				$items   = array_values( $items );
				$items[] = array_merge( ( include codecept_data_dir( 'order-items/fee.php' ) )[1], [ 'id' => "fee_9687_{$vip}", 'ticket_id' => $vip ] );

				return $items;
			}
		);
		$cart = tribe( Cart::class );
		$this->set_fn_return( 'wp_generate_password', 'abcdefghijklmnop' );
		$cart->get_cart_hash( true );
		$cart->get_repository()->upsert_item( $vip, 1 );
		$cart->get_repository()->upsert_item( $ga, 2 );
		$order  = $this->create_order_without_transitions();
		$before = $this->get_rows( $order->ID );
		$cart->get_repository()->remove_item( $ga );

		$updated = $this->create_order_without_transitions();
		$cart->clear_cart();

		$this->assertSame( $order->ID, $updated->ID );
		$after = $this->get_rows( $order->ID );
		$this->assertSame( [ "{$vip}:0:0", "{$vip}:9687:0" ], array_keys( $after ) );
		$this->assertSame( $before[ "{$vip}:0:0" ], $after[ "{$vip}:0:0" ] );
		$this->assertSame( $before[ "{$vip}:9687:0" ]['id'], $after[ "{$vip}:9687:0" ]['id'] );
		$this->assertSame( [ '2', '1' ], [ $before[ "{$vip}:9687:0" ]['item_key'], $after[ "{$vip}:9687:0" ]['item_key'] ] );
		$this->assert_rows_hold_the_items( $order->ID );
		$this->assertSame( '2', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
	}

	public function test_an_update_syncs_rows_by_line_and_the_same_items_again_change_nothing(): void {
		$this->register_controller( true );
		[ $order_id, $before, $items ] = $this->make_order_and_new_items();
		$queries                       = $this->count_queries_against_the_table();

		$this->save_items( $order_id, $items );

		$this->assertSame( [ 'DELETE' => 1, 'UPDATE' => 1, 'INSERT' => 1 ], array_diff_key( $queries(), [ 'SELECT' => true ] ) );
		$after = $this->get_rows( $order_id );
		$this->assertSame( array_keys( $after ), array_map( [ $this, 'identity' ], array_values( $items ) ) );
		$vip = array_key_first( $before );
		$this->assertSame( $before[ $vip ]['id'], $after[ $vip ]['id'] );
		$this->assertSame( $before[ $vip ]['created_at'], $after[ $vip ]['created_at'] );
		$this->assert_rows_hold_the_items( $order_id );
		$this->assertSame( '2', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );

		// WordPress fires no update for an unchanged meta value, so sync the same items directly.
		$queries = $this->count_queries_against_the_table();
		do_action( 'tec_tickets_commerce_order_updated', $order_id, $items );

		$this->assertSame( [], array_diff_key( $queries(), [ 'SELECT' => true ] ) );
		$this->assertSame( $after, $this->get_rows( $order_id ) );
		$this->assertSame( '2', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );
	}

	public function test_a_line_added_in_the_middle_is_inserted_there_and_moves_the_lines_after_it(): void {
		$this->register_controller( true );
		[ , [ $vip, $ga ] ] = $this->make_tickets();
		$this->create_fee_for_ticket( $vip, [ 'raw_amount' => 2.5 ] );
		$order  = $this->create_order( [ $vip => 1 ], [ 'order_status' => Pending::SLUG ] );
		$items  = array_values( get_post_meta( $order->ID, Order::$items_meta_key, true ) );
		$before = $this->get_rows( $order->ID );
		$fee    = $this->identity( $items[1] );
		$this->assertSame( [ "{$vip}:0:0", $fee ], array_keys( $before ) );
		$queries = $this->count_queries_against_the_table();

		$this->save_items( $order->ID, [ $items[0], array_merge( $items[0], [ 'ticket_id' => $ga ] ), $items[1] ] );

		$this->assertSame( [ 'UPDATE' => 1, 'INSERT' => 1 ], array_diff_key( $queries(), [ 'SELECT' => true ] ) );
		$after = $this->get_rows( $order->ID );
		$this->assertSame( [ "{$vip}:0:0" => 0, "{$ga}:0:0" => 1, $fee => 2 ], $this->positions( $after ) );
		$this->assertSame( $before[ "{$vip}:0:0" ], $after[ "{$vip}:0:0" ] );
		$this->assertSame( $before[ $fee ]['id'], $after[ $fee ]['id'] );
		$this->assert_rows_hold_the_items( $order->ID );
	}

	public function test_a_reorder_updates_only_the_moved_lines(): void {
		$this->register_controller( true );
		[ $event_id, [ $vip, $ga ] ] = $this->make_tickets();
		$student                     = $this->create_tc_ticket( $event_id, 5 );
		$order                       = $this->create_order( [ $vip => 1, $ga => 1, $student => 1 ], [ 'order_status' => Pending::SLUG ] );
		$items                       = get_post_meta( $order->ID, Order::$items_meta_key, true );
		[ $first, $second, $third ]  = array_keys( $items );
		$before                      = $this->get_rows( $order->ID );
		$queries                     = $this->count_queries_against_the_table();

		// Keys stay with their items, so only the positions tell the new order.
		do_action( 'tec_tickets_commerce_order_updated', $order->ID, [ $first => $items[ $first ], $third => $items[ $third ], $second => $items[ $second ] ] );

		$this->assertSame( [ 'UPDATE' => 2 ], array_diff_key( $queries(), [ 'SELECT' => true ] ) );
		$after = $this->get_rows( $order->ID );
		$moved = array_keys( array_diff_assoc( $this->positions( $before ), $this->positions( $after ) ) );
		$this->assertSame( [ $this->identity( $items[ $second ] ), $this->identity( $items[ $third ] ) ], $moved );
		$this->assertSame( [ $this->identity( $items[ $first ] ), $this->identity( $items[ $third ] ), $this->identity( $items[ $second ] ) ], array_keys( $after ) );
		$ids = array_column( $after, 'id', 'ticket_id' );
		ksort( $ids );
		$this->assertSame( array_column( $before, 'id', 'ticket_id' ), $ids );
		$this->assertSame( $before[ $this->identity( $items[ $first ] ) ], $after[ $this->identity( $items[ $first ] ) ] );
	}

	public function test_an_update_of_an_order_stored_the_old_way_writes_nothing(): void {
		[ , $ticket_ids ] = $this->make_tickets();
		$order            = $this->create_order( [ $ticket_ids[0] => 1 ] );
		$this->register_controller( true );

		$this->save_items( $order->ID, get_post_meta( $order->ID, Order::$items_meta_key, true ) + [ 5 => [ 'ticket_id' => $ticket_ids[1], 'type' => 'ticket', 'quantity' => 1 ] ] );

		$this->assertSame( 0, Order_Items_Table::get_total_items() );
		$this->assertSame( '', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
	}

	public function failed_sync_provider(): Generator {
		foreach ( [ 'insert_many', 'update_rows', 'delete_many' ] as $method ) {
			yield "{$method} fails" => [
				function ( array $items ) use ( $method ) {
					$this->set_class_fn_return(
						Order_Items_Repository::class,
						$method,
						static function () {
							throw new RuntimeException( 'Query failed.' );
						},
						true
					);

					return $items;
				},
			];
		}

		yield 'duplicate line' => [
			function ( array $items ) {
				$items[] = reset( $items );

				return $items;
			},
		];
	}

	/**
	 * @dataProvider failed_sync_provider
	 */
	public function test_a_failed_sync_leaves_no_rows_and_the_order_reading_its_new_items( Closure $break_the_sync ): void {
		$this->register_controller( true );
		[ $order_id, , $items ] = $this->make_order_and_new_items();
		$items                  = $break_the_sync->call( $this, $items );

		$this->save_items( $order_id, $items );

		$this->assertSame( 0, Order_Items_Table::get_total_items() );
		$this->assertSame( '', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );
		$this->assertEquals( $items, tec_tc_get_order( $order_id )->items );
		$this->assert_logged( 'debug', 'could not be synced' );
	}

	public function test_an_interrupted_sync_leaves_the_order_reading_its_new_items(): void {
		$this->register_controller( true );
		[ $order_id, $before, $items ] = $this->make_order_and_new_items();
		// A fatal error here would stop PHP before the rows are cleaned up.
		foreach ( [ 'get_by_order', 'delete_by_order' ] as $method ) {
			$this->set_class_fn_return(
				Order_Items_Repository::class,
				$method,
				static function () {
					throw new Error( 'Crashed.' );
				},
				true
			);
		}

		$this->save_items( $order_id, $items );

		$this->assertSame( count( $before ), Order_Items_Table::get_total_items() );
		$this->assertSame( '', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );
		$this->assertEquals( $items, tec_tc_get_order( $order_id )->items );
	}

	public function test_an_outer_transaction_rollback_undoes_the_item_change_and_the_sync(): void {
		$this->register_controller( true );
		[ $order_id, $before, $items ] = $this->make_order_and_new_items();
		$old_items                     = get_post_meta( $order_id, Order::$items_meta_key, true );
		$post_ids                      = array_merge( [ $order_id ], wp_list_pluck( $old_items, 'event_id' ), wp_list_pluck( array_merge( $old_items, $items ), 'ticket_id' ) );

		tec_tickets_tests_fake_transactions_disable();

		try {
			DB::beginTransaction();
			$this->save_items( $order_id, $items );
			$this->assertNotSame( $before, $this->get_rows( $order_id ) );
			DB::rollback();
			wp_cache_flush();

			$this->assertSame( $before, $this->get_rows( $order_id ) );
			$this->assertSame( $old_items, get_post_meta( $order_id, Order::$items_meta_key, true ) );
			$this->assertSame( '2', get_post_meta( $order_id, Writer::VERSION_META_KEY, true ) );
		} finally {
			tec_tickets_tests_fake_transactions_enable();
			foreach ( array_unique( $post_ids ) as $post_id ) {
				wp_delete_post( $post_id, true );
			}
			$this->empty_table();
			// Beginning the outer transaction committed the test's own: persist the clean up.
			DB::query( 'COMMIT' );
		}
	}

	/**
	 * Creates a pending stored order of a VIP and a GA ticket, and the items of the same order with the VIP quantity changed,
	 * the GA line gone and a new ticket line added.
	 *
	 * @return array{0: int, 1: array<string,array<string,mixed>>, 2: array} The order ID, its rows and the new items.
	 */
	private function make_order_and_new_items(): array {
		[ $event_id, [ $vip, $ga ] ] = $this->make_tickets();
		$new_ticket                  = $this->create_tc_ticket( $event_id, 30 );
		$order                       = $this->create_order( [ $vip => 1, $ga => 2 ], [ 'order_status' => Pending::SLUG ] );
		$items                       = array_values( get_post_meta( $order->ID, Order::$items_meta_key, true ) );
		$this->assertEquals( [ $vip, $ga ], array_column( $items, 'ticket_id' ) );
		$items[0] = array_merge( $items[0], [ 'quantity' => 3, 'sub_total' => 30.0, 'regular_sub_total' => 30.0 ] );
		$items[1] = array_merge( $items[0], [ 'ticket_id' => $new_ticket, 'quantity' => 1, 'price' => 30.0, 'regular_price' => 30.0, 'sub_total' => 30.0, 'regular_sub_total' => 30.0 ] );

		return [ $order->ID, $this->get_rows( $order->ID ), $items ];
	}

	private function save_items( int $order_id, array $items ): void {
		tec_tc_orders()->by_args( [ 'id' => $order_id, 'status' => 'any' ] )->set_args( [ 'items' => $items ] )->save();
	}

	/**
	 * @return array<string,array<string,mixed>> The order's rows keyed by line identity, with dates as strings.
	 */
	private function get_rows( int $order_id ): array {
		$rows = [];

		foreach ( tribe( Order_Items_Repository::class )->get_by_order( $order_id ) as $model ) {
			$row = array_map(
				static fn( $value ) => $value instanceof DateTimeInterface ? $value->format( 'Y-m-d H:i:s' ) : $value,
				$model->toArray()
			);

			$rows[ $this->identity( $row ) ] = $row;
		}

		return $rows;
	}

	/**
	 * @param array<string,array<string,mixed>> $rows Rows keyed by line identity.
	 *
	 * @return array<string,int> The rows' positions, keyed by line identity.
	 */
	private function positions( array $rows ): array {
		return array_map( static fn( array $row ) => $row['position'], $rows );
	}

	private function identity( array $line ): string {
		return implode( ':', [ $line['ticket_id'], $line['modifier_id'] ?? $line['fee_id'] ?? 0, $line['purchase_rule_id'] ?? 0 ] );
	}

	private function assert_rows_hold_the_items( int $order_id ): void {
		$rebuilt = [];

		foreach ( tribe( Order_Items_Repository::class )->get_by_order( $order_id ) as $model ) {
			$row             = $model->toArray();
			[ $key, $item ]  = tribe( Line_Item_Types::class )->get( $row['type'] )->from_row( $row );
			$rebuilt[ $key ] = $item;
		}

		$items = get_post_meta( $order_id, Order::$items_meta_key, true );
		$this->assertEquals( $items, $rebuilt );
		$this->assertSame( serialize( $items ), serialize( $rebuilt ) );
	}

	/**
	 * Registers the Order Items controller, which registers the writer while the switch is on.
	 */
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
		$counts = [];
		$table  = Order_Items_Table::table_name();

		add_filter(
			'query',
			static function ( $query ) use ( &$counts, $table ) {
				if ( false !== strpos( $query, "`{$table}`" ) ) {
					$statement            = strtoupper( strtok( ltrim( $query ), ' ' ) );
					$counts[ $statement ] = ( $counts[ $statement ] ?? 0 ) + 1;
				}

				return $query;
			}
		);

		return static function () use ( &$counts ): array {
			return $counts;
		};
	}
}
