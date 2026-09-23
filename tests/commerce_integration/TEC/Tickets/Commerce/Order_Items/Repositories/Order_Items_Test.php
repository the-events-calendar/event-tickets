<?php

namespace TEC\Tickets\Commerce\Order_Items\Repositories;

use Codeception\TestCase\WPTestCase;
use DateTimeInterface;
use Generator;
use InvalidArgumentException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Order_Items\Models\Order_Item;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;

class Order_Items_Test extends WPTestCase {
	/**
	 * The table's update_many() opens its own transaction, which commits the test's: rows must be removed by hand.
	 *
	 * @after
	 */
	public function empty_table(): void {
		DB::query( DB::prepare( 'DELETE FROM %i', Order_Items_Table::table_name() ) );
	}

	public function test_insert_many_writes_every_row_in_one_query(): void {
		$rows    = array_map( fn( int $ticket_id ) => $this->row( 1, $ticket_id ), range( 1, 5 ) );
		$queries = $this->count_queries_against_the_table();

		$inserted = tribe( Order_Items::class )->insert_many( $rows );

		$this->assertSame( count( $rows ), $inserted );
		$this->assertSame( [ 'INSERT' => 1 ], $queries() );
		$this->assertSame( count( $rows ), Order_Items_Table::get_total_items() );
	}

	public function test_insert_many_runs_no_query_without_rows(): void {
		$queries = $this->count_queries_against_the_table();

		$this->assertSame( 0, tribe( Order_Items::class )->insert_many( [] ) );
		$this->assertSame( [], $queries() );
	}

	public function test_get_by_order_returns_the_order_rows_in_insertion_order_in_one_query(): void {
		$repository = tribe( Order_Items::class );
		$repository->insert_many( [ $this->row( 1, 30 ), $this->row( 2, 30 ), $this->row( 1, 10 ) ] );
		$repository->insert_many( [ $this->row( 1, 20 ) ] );
		$queries = $this->count_queries_against_the_table();

		$models = $repository->get_by_order( 1 );

		$this->assertSame( [ 'SELECT' => 1 ], $queries() );
		$this->assertContainsOnlyInstancesOf( Order_Item::class, $models );
		$rows = $this->get_rows( 1 );
		$this->assertSame( [ 30, 10, 20 ], array_column( $rows, 'ticket_id' ) );
		$ids = array_column( $rows, 'id' );
		sort( $ids );
		$this->assertSame( $ids, array_column( $rows, 'id' ) );
	}

	public function test_update_many_keeps_row_ids_and_changes_only_the_given_rows(): void {
		$repository = tribe( Order_Items::class );
		$repository->insert_many( [ $this->row( 1, 1 ), $this->row( 1, 2 ), $this->row( 1, 3 ) ] );
		[ $first, $second, $third ] = $this->get_rows( 1 );

		$updated = $repository->update_many(
			[
				[ 'id' => $first['id'], 'quantity' => 4, 'item_key' => '2' ],
				[ 'id' => $third['id'], 'quantity' => 5, 'item_key' => '0' ],
			]
		);

		$this->assertTrue( $updated );
		$this->assertSame(
			[
				array_merge( $first, [ 'quantity' => 4, 'item_key' => '2' ] ),
				$second,
				array_merge( $third, [ 'quantity' => 5, 'item_key' => '0' ] ),
			],
			$this->get_rows( 1 )
		);
	}

	public function test_delete_by_order_leaves_other_orders_rows(): void {
		$repository = tribe( Order_Items::class );
		$repository->insert_many( [ $this->row( 1, 1 ), $this->row( 1, 2 ), $this->row( 2, 1 ) ] );
		$other_order = $this->get_rows( 2 );
		$queries     = $this->count_queries_against_the_table();

		$this->assertSame( 2, $repository->delete_by_order( 1 ) );
		$this->assertSame( [ 'DELETE' => 1 ], $queries() );
		$this->assertSame( [], $repository->get_by_order( 1 ) );
		$this->assertSame( $other_order, $this->get_rows( 2 ) );
	}

	public function test_delete_many_removes_only_the_given_rows(): void {
		$repository = tribe( Order_Items::class );
		$repository->insert_many( [ $this->row( 1, 1 ), $this->row( 1, 2 ), $this->row( 1, 3 ), $this->row( 2, 1 ) ] );
		[ $first, $second, $third ] = $this->get_rows( 1 );
		$other_order                = $this->get_rows( 2 );
		$queries                    = $this->count_queries_against_the_table();

		$this->assertSame( 2, $repository->delete_many( [ $first['id'], $third['id'] ] ) );
		$this->assertSame( [ 'DELETE' => 1 ], $queries() );
		$this->assertSame( [ $second ], $this->get_rows( 1 ) );
		$this->assertSame( $other_order, $this->get_rows( 2 ) );
	}

	public function test_nullable_columns_negative_amounts_and_currency_round_trip(): void {
		$nullable = [
			'item_key',
			'event_id',
			'post_id',
			'occurrence_id',
			'event_title',
			'event_start_date',
			'event_start_date_utc',
			'sku',
			'ticket_type',
			'regular_price',
			'regular_sub_total',
			'extra',
		];
		$coupon   = array_merge(
			$this->row( 1, 0 ),
			array_fill_keys( $nullable, null ),
			[
				'type'        => 'coupon',
				'modifier_id' => 9,
				'currency'    => 'EUR',
				'price'       => -500,
				'sub_total'   => -500,
			]
		);
		$repository = tribe( Order_Items::class );
		$repository->insert_many( [ $coupon ] );

		[ $model ] = $repository->get_by_order( 1 );

		$this->assertSame( array_fill_keys( $nullable, null ), array_map( [ $model, 'getAttribute' ], array_combine( $nullable, $nullable ) ) );
		$this->assertSame( [ 'EUR', -500, -500 ], [ $model->get_currency(), $model->get_price(), $model->get_sub_total() ] );
	}

	public function test_insert_many_writes_rows_with_keys_in_any_order_to_the_right_columns(): void {
		$first  = $this->row( 1, 1 );
		$second = array_reverse( array_merge( $this->row( 1, 2 ), [ 'name' => 'VIP', 'quantity' => 3 ] ), true );

		tribe( Order_Items::class )->insert_many( [ $first, $second ] );

		foreach ( [ $first, $second ] as $index => $expected ) {
			ksort( $expected );
			$stored = array_intersect_key( $this->get_rows( 1 )[ $index ], $expected );
			ksort( $stored );
			$this->assertSame( $expected, $stored );
		}
	}

	public function mismatched_row_provider(): Generator {
		yield 'missing key' => [ static fn( array $row ) => array_diff_key( $row, [ 'name' => true ] ) ];
		yield 'extra key' => [ static fn( array $row ) => array_merge( $row, [ 'sku' => 'VIP-1' ] ) ];
	}

	/**
	 * @dataProvider mismatched_row_provider
	 */
	public function test_insert_many_rejects_a_row_whose_columns_differ_from_the_first(): void {
		$change = func_get_arg( 0 );

		try {
			tribe( Order_Items::class )->insert_many( [ $this->row( 1, 1 ), $this->row( 1, 2 ), $change( $this->row( 1, 3 ) ) ] );
			$this->fail( 'A row with different columns was accepted.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( 'Row 2', $e->getMessage() );
		}

		$this->assertSame( 0, Order_Items_Table::get_total_items() );
	}

	public function model_finder_provider(): Generator {
		yield 'model find' => [ static fn( int $id ) => Order_Item::find( $id ) ];
		yield 'repository by primary key' => [ static fn( int $id ) => tribe( Order_Items::class )->by_primary_key( $id ) ];
	}

	/**
	 * @dataProvider model_finder_provider
	 */
	public function test_a_row_is_found_by_id_as_a_model(): void {
		$find = func_get_arg( 0 );
		tribe( Order_Items::class )->insert_many( [ $this->row( 1, 1 ) ] );
		[ $stored ] = $this->get_rows( 1 );

		$model = $find( $stored['id'] );

		$this->assertInstanceOf( Order_Item::class, $model );
		$this->assertSame( $stored, $this->to_row( $model ) );
		$this->assertNull( $find( $stored['id'] + 1 ) );
	}

	/**
	 * A row with every NOT NULL column set: the table has no column defaults.
	 *
	 * @return array<string,int|string>
	 */
	private function row( int $order_id, int $ticket_id ): array {
		return [
			'order_id'         => $order_id,
			'type'             => 'ticket',
			'item_key'         => "{$ticket_id}",
			'ticket_id'        => $ticket_id,
			'modifier_id'      => 0,
			'purchase_rule_id' => 0,
			'name'             => 'General Admission',
			'currency'         => 'USD',
			'quantity'         => 1,
			'price'            => 1050,
			'sub_total'        => 1050,
			'created_at'       => gmdate( 'Y-m-d H:i:s' ),
		];
	}

	/**
	 * Fetches an order's rows as arrays with dates as strings, so rows fetched at different times compare with assertSame.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_rows( int $order_id ): array {
		return array_map( [ $this, 'to_row' ], tribe( Order_Items::class )->get_by_order( $order_id ) );
	}

	/**
	 * @return array<string,mixed> The model's values, with dates as strings.
	 */
	private function to_row( Order_Item $model ): array {
		return array_map(
			static fn( $value ) => $value instanceof DateTimeInterface ? $value->format( 'Y-m-d H:i:s' ) : $value,
			$model->toArray()
		);
	}

	/**
	 * Starts counting the queries run against the table, by statement.
	 *
	 * @return callable(): array<string,int> Returns the counts so far, keyed by statement.
	 */
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
