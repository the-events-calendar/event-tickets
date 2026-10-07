<?php

namespace TEC\Tickets\Recurring_Tickets\Repositories;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
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
