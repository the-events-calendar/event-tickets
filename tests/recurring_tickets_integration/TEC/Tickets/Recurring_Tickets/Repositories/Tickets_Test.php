<?php

namespace TEC\Tickets\Recurring_Tickets\Repositories;

use Codeception\TestCase\WPTestCase;
use Generator;
use InvalidArgumentException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets as Tickets_Table;

/**
 * The rows of a date, a post or a template can be stored, listed and removed in one query each.
 */
class Tickets_Test extends WPTestCase {
	/**
	 * @after
	 */
	public function empty_table(): void {
		( new Tickets_Table() )->empty_table();
	}

	/**
	 * @test
	 */
	public function it_should_insert_many_rows_in_one_query(): void {
		$inserts = $this->count_inserts();

		$inserted = tribe( Tickets::class )->insert_many(
			array_map( fn( int $occurrence_id ) => $this->row( [ 'occurrence_id' => $occurrence_id ] ), range( 1, 5 ) )
		);

		$this->assertSame( 5, $inserted );
		$this->assertSame( 1, $inserts() );
		$this->assertSame( 5, Tickets_Table::get_total_items() );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_rows_of_a_date_in_menu_order(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many(
			[
				$this->row( [ 'parent_id' => 1, 'menu_order' => 2, 'name' => 'Third' ] ),
				$this->row( [ 'parent_id' => 2, 'menu_order' => 0, 'name' => 'First' ] ),
				$this->row( [ 'parent_id' => 3, 'menu_order' => 1, 'name' => 'Second' ] ),
				$this->row( [ 'parent_id' => 1, 'occurrence_id' => 31, 'menu_order' => 0, 'name' => 'Another date' ] ),
			]
		);

		$rows = $repository->get_by_occurrence( 30 );

		$this->assertContainsOnlyInstancesOf( Ticket::class, $rows );
		$this->assertSame( [ 'First', 'Second', 'Third' ], array_column( $this->to_arrays( $rows ), 'name' ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_the_rows_of_a_post_and_of_a_template(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many(
			[
				$this->row( [ 'parent_id' => 1, 'occurrence_id' => 30, 'name' => 'A30' ] ),
				$this->row( [ 'parent_id' => 1, 'occurrence_id' => 31, 'name' => 'A31' ] ),
				$this->row( [ 'parent_id' => 2, 'occurrence_id' => 30, 'name' => 'B30' ] ),
				$this->row( [ 'parent_id' => 3, 'post_id' => 21, 'occurrence_id' => 40, 'name' => 'Other post' ] ),
			]
		);

		$this->assertSame( [ 'A30', 'A31', 'B30' ], array_column( $this->to_arrays( $repository->get_by_post( 20 ) ), 'name' ) );
		$this->assertSame( [ 'A30', 'A31' ], array_column( $this->to_arrays( $repository->get_by_template( 1 ) ), 'name' ) );
	}

	/**
	 * @test
	 */
	public function it_should_page_through_more_rows_than_one_query_returns(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( array_map( fn( int $occurrence_id ) => $this->row( [ 'occurrence_id' => $occurrence_id ] ), range( 1, 450 ) ) );

		$this->assertCount( 450, $repository->get_by_post( 20 ) );
	}

	/**
	 * @test
	 */
	public function it_should_delete_the_rows_of_a_date_a_template_or_a_post_only(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many(
			[
				$this->row( [ 'parent_id' => 1, 'occurrence_id' => 30 ] ),
				$this->row( [ 'parent_id' => 2, 'occurrence_id' => 30 ] ),
				$this->row( [ 'parent_id' => 1, 'occurrence_id' => 31 ] ),
				$this->row( [ 'parent_id' => 3, 'occurrence_id' => 32 ] ),
				$this->row( [ 'parent_id' => 4, 'post_id' => 21, 'occurrence_id' => 40 ] ),
			]
		);

		$this->assertSame( 2, $repository->delete_by_occurrence( 30 ) );
		$this->assertSame( [ 31, 32, 40 ], $this->occurrence_ids() );

		$this->assertSame( 1, $repository->delete_by_template( 1 ) );
		$this->assertSame( [ 32, 40 ], $this->occurrence_ids() );

		$this->assertSame( 1, $repository->delete_by_post( 20 ) );
		$this->assertSame( [ 40 ], $this->occurrence_ids() );
	}

	/**
	 * @test
	 */
	public function it_should_fill_the_defaults_a_writer_leaves_out(): void {
		$row = $this->row();
		unset( $row['sales'], $row['menu_order'], $row['created_at'] );
		$before = time();

		tribe( Tickets::class )->insert_many( [ $row ] );

		$stored = $this->to_arrays( tribe( Tickets::class )->get_by_occurrence( 30 ) )[0];
		$this->assertSame( 0, $stored['sales'] );
		$this->assertSame( 0, $stored['menu_order'] );
		$this->assertEqualsWithDelta( $before, strtotime( $this->format_date( $stored['created_at'] ) . ' UTC' ), 5 );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_rows_that_set_different_columns(): void {
		$second = $this->row( [ 'occurrence_id' => 31 ] );
		unset( $second['sku'] );

		$this->expectException( InvalidArgumentException::class );

		try {
			tribe( Tickets::class )->insert_many( [ $this->row(), $second ] );
		} finally {
			$this->assertSame( 0, Tickets_Table::get_total_items() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_store_json_columns_as_arrays(): void {
		$relative = [ 'start' => [ 'value' => 3, 'unit' => 86400, 'direction' => 'before', 'anchor' => 'start' ], 'end' => null ];
		tribe( Tickets::class )->insert_many( [ $this->row( [ 'relative_date_settings' => $relative, 'iac_settings' => [ 'iac' => 'required' ] ] ) ] );

		$stored = $this->to_arrays( tribe( Tickets::class )->get_by_occurrence( 30 ) )[0];

		$this->assertSame( $relative, $stored['relative_date_settings'] );
		$this->assertSame( [ 'iac' => 'required' ], $stored['iac_settings'] );
		$this->assertNull( $stored['overrides'] );
	}

	/**
	 * @test
	 */
	public function it_should_record_each_overridden_column_with_its_value(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row() ] );
		$id = $this->only_row()['id'];

		$repository->override( $id, [ 'price' => 1500 ] );

		$row = $this->only_row();
		$this->assertSame( 1500, $row['price'] );
		$this->assertSame( [ 'price' ], $row['overrides'] );

		$repository->override( $id, [ 'capacity' => 50 ] );

		$row = $this->only_row();
		$this->assertSame( 50, $row['capacity'] );
		$this->assertSame( [ 'price', 'capacity' ], $row['overrides'] );
	}

	/**
	 * @test
	 */
	public function it_should_list_a_column_overridden_twice_once(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row() ] );
		$id = $this->only_row()['id'];

		$repository->override( $id, [ 'price' => 1500, 'name' => 'Early bird' ] );
		$repository->override( $id, [ 'price' => 1200 ] );

		$row = $this->only_row();
		$this->assertSame( 1200, $row['price'] );
		$this->assertSame( [ 'price', 'name' ], $row['overrides'] );
	}

	/**
	 * @test
	 */
	public function it_should_accept_the_sale_dates_description_and_status(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row() ] );
		$values = [
			'description'    => 'Only on this date',
			'start_date'     => '2026-11-01 09:00:00',
			'end_date'       => '2026-11-03 09:00:00',
			'start_date_utc' => '2026-11-01 07:00:00',
			'end_date_utc'   => '2026-11-03 07:00:00',
			'status'         => 'draft',
		];

		$repository->override( $this->only_row()['id'], $values );

		$row = $this->only_row();
		$this->assertSame( array_keys( $values ), $row['overrides'] );
		$this->assertSame( 'draft', $row['status'] );
		$this->assertSame( '2026-11-01 09:00:00', $this->format_date( $row['start_date'] ) );
	}

	public function columns_that_cannot_be_overridden_provider(): Generator {
		yield 'sales' => [ [ 'sales' => 5 ] ];
		yield 'post' => [ [ 'post_id' => 99 ] ];
		yield 'stock' => [ [ 'stock' => 1 ] ];
		yield 'overrides itself' => [ [ 'overrides' => [ 'price' ] ] ];
		yield 'unknown' => [ [ 'nope' => 1 ] ];
		yield 'allowed with a refused one' => [ [ 'price' => 1500, 'sales' => 5 ] ];
		yield 'nothing' => [ [] ];
	}

	/**
	 * @test
	 * @dataProvider columns_that_cannot_be_overridden_provider
	 *
	 * @param array<string,mixed> $values The values to override.
	 */
	public function it_should_refuse_a_column_that_cannot_be_overridden( array $values ): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row() ] );
		$before = $this->only_row();

		try {
			$repository->override( $before['id'], $values );
			$this->fail( 'The override should have been refused.' );
		} catch ( InvalidArgumentException $e ) {
			// Equal, not identical: dates come back as new DateTime objects.
			$this->assertEquals( $before, $this->only_row() );
		}
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_row_that_does_not_exist(): void {
		$this->expectException( InvalidArgumentException::class );

		tribe( Tickets::class )->override( 999, [ 'price' => 1500 ] );
	}

	/**
	 * @test
	 */
	public function it_should_keep_stock_consistent_when_capacity_is_overridden(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row( [ 'sales' => 3, 'stock' => 97 ] ) ] );
		$id = $this->only_row()['id'];

		$repository->override( $id, [ 'capacity' => 10 ] );
		$this->assertSame( 7, $this->only_row()['stock'] );

		$repository->override( $id, [ 'capacity' => 2 ] );
		$this->assertSame( 0, $this->only_row()['stock'], 'Capacity below sales leaves nothing to sell.' );

		$repository->override( $id, [ 'capacity' => -1 ] );
		$this->assertNull( $this->only_row()['stock'], 'An unlimited row has no stock.' );
	}

	/**
	 * @return array<string,mixed> The attributes of the one row in the table.
	 */
	private function only_row(): array {
		$rows = $this->to_arrays( tribe( Tickets::class )->get_by_post( 20 ) );
		$this->assertCount( 1, $rows );

		return $rows[0];
	}

	/**
	 * @test
	 */
	public function it_should_read_a_row_by_id_once(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row() ] );
		$id      = $this->only_row()['id'];
		$queries = 0;
		add_filter(
			'query',
			static function ( string $query ) use ( &$queries ) {
				$queries += false !== strpos( $query, Tickets_Table::table_name() ) ? 1 : 0;

				return $query;
			}
		);

		$repository->find( $id );
		$repository->find( $id );

		$this->assertSame( 1, $queries );
	}

	/**
	 * @test
	 */
	public function it_should_find_a_row_inserted_after_it_was_read_as_missing(): void {
		$repository = tribe( Tickets::class );
		$next       = (int) DB::get_var( DB::prepare( 'SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s', Tickets_Table::table_name() ) );
		$this->assertNull( $repository->find( $next ) );

		$repository->insert_many( [ $this->row() ] );

		$this->assertNotNull( $repository->find( $next ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_find_a_row_deleted_after_it_was_read(): void {
		$repository = tribe( Tickets::class );
		$repository->insert_many( [ $this->row( [ 'parent_id' => 7 ] ) ] );
		$id = $this->only_row()['id'];

		$repository->delete_by_template( 7 );

		$this->assertNull( $repository->find( $id ) );
	}

	/**
	 * Counts the insert queries run against the table from now on.
	 *
	 * @return callable(): int Returns the count so far.
	 */
	private function count_inserts(): callable {
		$count = 0;
		add_filter(
			'query',
			static function ( string $query ) use ( &$count ) {
				if ( 0 === stripos( ltrim( $query ), 'INSERT INTO `' . Tickets_Table::table_name() . '`' ) ) {
					++$count;
				}

				return $query;
			}
		);

		return static function () use ( &$count ): int {
			return $count;
		};
	}

	/**
	 * @return int[] The occurrence IDs of the rows left, ascending.
	 */
	private function occurrence_ids(): array {
		$ids = array_column( $this->to_arrays( tribe( Tickets::class )->get_by_post( 20 ) ), 'occurrence_id' );
		$ids = array_merge( $ids, array_column( $this->to_arrays( tribe( Tickets::class )->get_by_post( 21 ) ), 'occurrence_id' ) );
		sort( $ids );

		return $ids;
	}

	/**
	 * @param Ticket[] $rows The models.
	 *
	 * @return array<int,array<string,mixed>> The models' attributes.
	 */
	private function to_arrays( array $rows ): array {
		return array_map( static fn( Ticket $row ) => $row->toArray(), $rows );
	}

	/**
	 * @param mixed $date A date as the model returns it.
	 */
	private function format_date( $date ): string {
		return $date instanceof \DateTimeInterface ? $date->format( 'Y-m-d H:i:s' ) : (string) $date;
	}

	/**
	 * @param array<string,mixed> $overrides Column values to replace the defaults with.
	 *
	 * @return array<string,mixed>
	 */
	private function row( array $overrides = [] ): array {
		return array_merge(
			[
				'type'          => 'recurring',
				'parent_id'     => 10,
				'post_id'       => 20,
				'occurrence_id' => 30,
				'name'          => 'General Admission',
				'sku'           => 'GA',
				'price'         => 1050,
				'capacity'      => 100,
				'stock'         => 100,
				'sales'         => 0,
				'menu_order'    => 0,
				'created_at'    => '2026-10-01 00:00:00',
			],
			$overrides
		);
	}
}
