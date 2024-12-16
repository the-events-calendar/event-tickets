<?php
/**
 * Abstract Custom Table class for handling common operations on custom tables, such as adding indexes.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\DB\DB;

/**
 * Abstract class that provides utility methods for managing custom table schemas,
 * including adding indexes.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables
 */
abstract class Abstract_Custom_Table extends Table {

	/**
	 * Helper method to check and add an index to a table.
	 *
	 * @since 5.18.0
	 *
	 * @param array  $results    The results array to track changes.
	 * @param string $index_name The name of the index.
	 * @param string $columns    The columns to index.
	 *
	 * @return array The updated results array.
	 */
	protected function check_and_add_index( array $results, string $index_name, string $columns ): array {
		$table_name = esc_sql( static::table_name( true ) );
		$columns    = esc_sql( $columns );
		$index_name = esc_sql( $index_name );

		// Add index only if it does not exist.
		if ( $this->has_index( $index_name ) ) {
			return $results;
		}

		$sql = "ALTER TABLE %i ADD INDEX `{$index_name}` ( {$columns} )";

		$updated = DB::query(
			DB::prepare( $sql, $table_name )
		);

		$message = $updated ?
			sprintf( 'Added index to the %s table on %s.', $table_name, $columns ) :
			sprintf( 'Failed to add an index on the %s table for %s.', $table_name, $columns );

		$results[ "{$table_name}.{$columns}" ] = $message;
		return $results;
	}

	/**
	 * Empties the custom table in a way that is not causing an implicit commit.
	 *
	 * Even though the method is called truncate it doesn't use TRUNCATE.
	 * Thats because we want to avoid implicit commits in the DB making this method suitable for using during a testcase.
	 * If you want to use TRUNCATE you can use the `empty_table` method instead.
	 *
	 * @since 5.18.0
	 *
	 * @return bool Whether it was emptied or not.
	 */
	public function truncate(): bool {
		if ( ! $this->exists() ) {
			// There is really nothing to do.
			return true;
		}

		$this_table = static::table_name( true );

		DB::query( 'SET foreign_key_checks = 0' );
		$result = DB::query( "DELETE FROM {$this_table}" );
		DB::query( 'SET foreign_key_checks = 1' );

		return is_numeric( $result );
	}
}
