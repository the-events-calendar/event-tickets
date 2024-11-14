<?php
/**
 * Abstract Custom Table class for handling common operations on custom tables, such as adding indexes.
 *
 * @since TBD
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
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables
 */
abstract class Abstract_Custom_Table extends Table {

	/**
	 * Helper method to check and add an index to a table.
	 *
	 * @since TBD
	 *
	 * @param array  $results    The results array to track changes.
	 * @param string $table_name The name of the table.
	 * @param string $index_name The name of the index.
	 * @param string $columns    The columns to index.
	 *
	 * @return array The updated results array.
	 */
	protected function check_and_add_index( array $results, string $table_name, string $index_name, string $columns ): array {
		$table_name = esc_sql( $table_name );
		$columns    = esc_sql( $columns );

		// Add index only if it does not exist.
		if ( $this->has_index( $index_name ) ) {
			return $results;
		}

		$sql = "ALTER TABLE `{$table_name}` ADD INDEX `{$index_name}` ( {$columns} )";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$updated = DB::query( $sql );

		$message = $updated ?
			sprintf( 'Added index to the %s table on %s.', $table_name, $columns ) :
			sprintf( 'Failed to add an index on the %s table for %s.', $table_name, $columns );

		$results[ "{$table_name}.{$columns}" ] = $message;
		return $results;
	}
}
