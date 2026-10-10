<?php

namespace TEC\Tickets\Commerce\Order_Items\Repositories;

use Codeception\TestCase\WPTestCase;
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
