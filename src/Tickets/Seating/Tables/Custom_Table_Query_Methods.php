<?php
/**
 * Provides query methods common to all custom tables.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use Generator;
use TEC\Common\StellarWP\DB\DB;

/**
 * Trait Custom_Table_Query_Methods.
 *
 * @since   5.16.0
 *
 * @package TEC\Controller\Tables;
 */
trait Custom_Table_Query_Methods {
	use Truncate_Methods;

	/**
	 * Fetches all the rows from the table using a batched query.
	 *
	 * @since 5.16.0
	 *
	 * @param int    $batch_size   The number of rows to fetch per batch.
	 * @param string $output       The output type of the query, one of OBJECT, ARRAY_A, or ARRAY_N.
	 * @param string $where_clause The optional WHERE clause to use.
	 *
	 * @return Generator<array<string, mixed>> The rows from the table.
	 */
	public static function fetch_all( int $batch_size = 50, string $output = OBJECT, string $where_clause = '' ): Generator {
		$fetched = 0;
		$total   = null;
		$offset  = 0;

		do {
			// On first iteration, we need to set the SQL_CALC_FOUND_ROWS flag.
			$sql_calc_found_rows = 0 === $fetched ? 'SQL_CALC_FOUND_ROWS' : '';

			$batch = DB::get_results(
				DB::prepare(
					"SELECT {$sql_calc_found_rows} * FROM %i {$where_clause} ORDER BY id LIMIT %d, %d",
					static::table_name( true ),
					$offset,
					$batch_size
				),
				$output
			);

			// We need to get the total number of rows, only after the first batch.
			$total  ??= DB::get_var( 'SELECT FOUND_ROWS()' );
			$fetched += count( $batch );

			yield from $batch;
		} while ( $fetched < $total );
	}

	/**
	 * Inserts multiple rows into the table.
	 *
	 * @since 5.16.0
	 *
	 * @param array<mixed> $entries The entries to insert.
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public static function insert_many( array $entries ) {
		$columns          = array_keys( $entries[0] );
		$prepared_columns = implode(
			', ',
			array_map(
				static fn( string $column ) => "`$column`",
				$columns
			)
		);
		$prepared_values  = implode(
			', ',
			array_map(
				static function ( array $entry ) use ( $columns ) {
					return '(' . implode( ', ', array_map( static fn( $e ) => DB::prepare( '%s', $e ), $entry ) ) . ')';
				},
				$entries
			)
		);

		return DB::query(
			DB::prepare(
				"INSERT INTO %i ({$prepared_columns}) VALUES {$prepared_values}",
				static::table_name( true ),
			)
		);
	}

	/**
	 * Fetches all the rows from the table using a batched query and a WHERE clause.
	 *
	 * @since 5.16.0
	 *
	 * @param string $where_clause The WHERE clause to use.
	 * @param int    $batch_size   The number of rows to fetch per batch.
	 * @param string $output       The output type of the query, one of OBJECT, ARRAY_A, or ARRAY_N.
	 *
	 * @return Generator<array<string, mixed>> The rows from the table.
	 */
	public static function fetch_all_where( string $where_clause, int $batch_size = 50, string $output = OBJECT ): Generator {
		return static::fetch_all( $batch_size, $output, $where_clause );
	}

	/**
	 * Fetches the first row from the table using a WHERE clause.
	 *
	 * @since 5.16.0
	 *
	 * @param string $where_clause The prepared WHERE clause to use.
	 * @param string $output       The output type of the query, one of OBJECT, ARRAY_A, or ARRAY_N.
	 *
	 * @return array|object|null The row from the table, or `null` if no row was found.
	 */
	public static function fetch_first_where( string $where_clause, string $output = OBJECT ) {
		return DB::get_row(
			DB::prepare(
				"SELECT * FROM %i {$where_clause} LIMIT 1",
				static::table_name( true )
			),
			$output
		);
	}
}
