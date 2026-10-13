<?php

namespace TEC\Tickets\Commerce\Order_Items\Repositories;

use Closure;
use Codeception\TestCase\WPTestCase;
use ReflectionFunction;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;

class Cached_Order_Items_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Order_Items_Table() )->empty_table();
	}

	public function test_it_reads_the_table_once_until_the_repository_writes(): void {
		$cached   = tribe( Order_Items_Repository::class );
		$order_id = static::factory()->post->create();
		$count    = $this->count_queries_against_the_table();

		$cached->get_by_order( $order_id );
		$cached->get_by_order( $order_id );

		$this->assertSame( 1, $count(), 'The second read comes from the cache.' );

		$cached->delete_by_order( $order_id );
		$before = $count();
		$cached->get_by_order( $order_id );

		$this->assertSame( $before + 1, $count(), 'A write through the decorator invalidates the cache.' );
	}

	public function test_a_cached_read_can_be_serialized_and_loads_the_same_rows(): void {
		$cached   = tribe( Order_Items_Repository::class );
		$order_id = static::factory()->post->create();
		Order_Items_Table::insert( [ 'order_id' => $order_id, 'type' => 'ticket', 'position' => 0, 'ticket_id' => 0, 'modifier_id' => 0, 'purchase_rule_id' => 0, 'name' => 'GA', 'currency' => 'USD', 'quantity' => 1, 'price' => 1050, 'sub_total' => 1050 ] );

		$read = $cached->get_by_order( $order_id );

		// A persistent object cache serializes what it stores; the default one keeps it in a private property.
		$stored = ( fn() => $this->cache )->call( $GLOBALS['wp_object_cache'] )['tec_tickets_order_items'];
		$this->assertCount( 1, $read );
		$this->assertIsString( serialize( $stored ) );
		$this->assertEquals(
			array_map( static fn( $row ) => $row->toArray(), $read ),
			array_map( static fn( $row ) => $row->toArray(), $cached->get_by_order( $order_id ) )
		);
	}

	public function test_it_invalidates_the_cache_again_when_the_request_ends(): void {
		// A new decorator: the shared one may have scheduled its shutdown flush on hooks a previous test removed.
		$cached   = new Cached_Order_Items( tribe( Order_Items::class ) );
		$order_id = static::factory()->post->create();

		$cached->delete_by_order( $order_id );
		$this->assertSame( [], $cached->get_by_order( $order_id ) );

		// A transaction around the write committed after the read above cached the rows as they were.
		Order_Items_Table::insert( [ 'order_id' => $order_id, 'type' => 'ticket', 'position' => 0, 'ticket_id' => 0, 'modifier_id' => 0, 'purchase_rule_id' => 0, 'name' => 'GA', 'currency' => 'USD', 'quantity' => 1, 'price' => 1050, 'sub_total' => 1050 ] );
		$this->assertSame( [], $cached->get_by_order( $order_id ), 'The cache still holds the rows as they were.' );

		foreach ( $this->get_shutdown_callbacks_of( $cached ) as $callback ) {
			$callback();
		}

		$this->assertCount( 1, $cached->get_by_order( $order_id ) );
	}

	/**
	 * Returns the shutdown callbacks the decorator added.
	 *
	 * The shutdown action itself is not run: other callbacks on it end the test runner's output buffers.
	 *
	 * @param Order_Items_Repository $cached The decorator.
	 *
	 * @return Closure[] The callbacks.
	 */
	private function get_shutdown_callbacks_of( Order_Items_Repository $cached ): array {
		$found = [];

		foreach ( $GLOBALS['wp_filter']['shutdown']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				$function = $callback['function'];

				if ( $function instanceof Closure && ( new ReflectionFunction( $function ) )->getClosureThis() === $cached ) {
					$found[] = $function;
				}
			}
		}

		return $found;
	}

	private function count_queries_against_the_table(): callable {
		$count = 0;
		$table = Order_Items_Table::table_name();

		add_filter(
			'query',
			static function ( $query ) use ( &$count, $table ) {
				if ( 0 === stripos( ltrim( $query ), 'select' ) && false !== strpos( $query, "`{$table}`" ) ) {
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
