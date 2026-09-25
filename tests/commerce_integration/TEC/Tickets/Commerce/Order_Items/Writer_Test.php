<?php

namespace TEC\Tickets\Commerce\Order_Items;

use ArrayObject;
use Closure;
use Generator;
use RuntimeException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\Free\Gateway as Free_Gateway;
use TEC\Tickets\Commerce\Gateways\Manual\Order as Manual_Order;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Order_Items\Repositories\Order_Items as Order_Items_Repository;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;
use TEC\Tickets\Commerce\Status\Completed;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use WP_Post;

class Writer_Test extends Controller_Test_Case {
	use Order_Maker;
	use Ticket_Maker;
	use With_Uopz;

	protected string $controller_class = Controller::class;

	protected array $sub_controller_classes = [ Writer::class ];

	/**
	 * @after
	 */
	public function empty_table(): void {
		DB::query( DB::prepare( 'DELETE FROM %i', Order_Items_Table::table_name() ) );
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
		$this->assertSame( [ 'INSERT' => 1 ], $queries() );
		$rows = tribe( Order_Items_Repository::class )->get_by_order( $order->ID );
		$this->assertCount( count( $given_items ), $rows );
		$rows = array_map( static fn( $row ) => $row->toArray(), $rows );
		$this->assertSame( array_map( 'strval', array_keys( $given_items ) ), array_column( $rows, 'item_key' ) );
		$this->assertSame( range( 0, count( $given_items ) - 1 ), array_column( $rows, 'position' ) );
		$this->assertSame( '2', get_post_meta( $order->ID, Writer::VERSION_META_KEY, true ) );
		$this->assertSame( $given_items, get_post_meta( $order->ID, Order::$items_meta_key, true ) );
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
