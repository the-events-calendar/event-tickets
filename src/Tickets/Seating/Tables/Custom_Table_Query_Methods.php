<?php
/**
 * Provides query methods common to all custom tables.
 *
 * @since TBD
 *
 * @package TEC\Controller\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use Generator;
use TEC\Tickets\Seating\StellarWP\DB\DB;

/**
 * Trait Custom_Table_Query_Methods.
 *
 * @since TBD
 *
 * @package TEC\Controller\Tables;
 */
trait Custom_Table_Query_Methods {
	/**
	 * Fetches all the rows from the table using a batched query.
	 *
	 * @since TBD
	 *
	 * @param int $batch_size The number of rows to fetch per batch.
	 * @param string $output The output type of the query, one of OBJECT, ARRAY_A, or ARRAY_N.
	 *
	 * @return Generator<array<string, mixed>> The rows from the table.
	 */
	public static function fetch_all( int $batch_size = 50, string $output = OBJECT ): Generator {
		$fetched = 0;
		$total   = null;
		$offset  = 0;

		do {
			// On first iteration, we need to set the SQL_CALC_FOUND_ROWS flag.
			$sql_calc_found_rows = $fetched === 0 ? 'SQL_CALC_FOUND_ROWS' : '';

			$batch = DB::get_results(
				Db::prepare(
					"SELECT ${sql_calc_found_rows} * FROM %i LIMIT %d, %d",
					static::table_name( true ),
					$offset,
					$batch_size
				),
				$output
			);

			// We need to get the total number of rows, only after the first batch.
			$total   = $total ?? DB::get_var( 'SELECT FOUND_ROWS()' );
			$fetched += count( $batch );

			yield from $batch;
		} while ( $fetched < $total );
	}

	/**
	 * Truncates the table.
	 *
	 * @since TBD
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public static function truncate() {
		return DB::query(
			DB::prepare(
				"TRUNCATE TABLE %i",
				static::table_name( true )
			)
		);
	}

	/**
	 * Inserts multiple rows into the table.
	 *
	 * @since TBD
	 *
	 * @param array $entries
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public static function insert_many( array $entries ) {
		$columns          = array_keys( $entries[0] );
		$prepared_columns = implode( ', ', array_map(
			static fn( string $column ) => "`$column`",
			$columns
		) );
		$prepared_values = implode( ', ', array_map(
			static function ( array $entry ) use ( $columns ) {
				return '(' . implode( ', ', array_map( static fn( $e ) => DB::prepare( '%s', $e ), $entry ) ) . ')';
			},
			$entries
		) );

		return DB::query(
			Db::prepare(
				"INSERT INTO %i ({$prepared_columns}) VALUES {$prepared_values}",
				static::table_name( true ),
			)
		);
	}
}