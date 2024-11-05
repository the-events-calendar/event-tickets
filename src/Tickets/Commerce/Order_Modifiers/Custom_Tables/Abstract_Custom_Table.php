<?php
/**
 * Abstract Custom Table class for handling common operations on custom tables, such as adding indexes
 * and foreign key constraints.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables
 */

namespace TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use wpdb;

/**
 * Abstract class that provides utility methods for managing custom table schemas,
 * including adding indexes and foreign key constraints.
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
	 * @param wpdb   $wpdb       The WordPress database global.
	 * @param array  $results    The results array to track changes.
	 * @param string $table_name The name of the table.
	 * @param string $index_name The name of the index.
	 * @param string $columns    The columns to index.
	 *
	 * @return array The updated results array.
	 */
	protected function check_and_add_index( wpdb $wpdb, array $results, string $table_name, string $index_name, string $columns ): array {
		$table_name = esc_sql( $table_name );
		$columns    = esc_sql( $columns );

		// Add index only if it does not exist.
		if ( $this->has_index( $index_name ) ) {
			return $results;
		}

		$sql = "ALTER TABLE `{$table_name}` ADD INDEX `{$index_name}` ( {$columns} )";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$updated = $wpdb->query( $sql );

		$message = $updated ?
			sprintf( 'Added index to the %s table on %s.', $table_name, $columns ) :
			sprintf( 'Failed to add an index on the %s table for %s.', $table_name, $columns );

		$results[ "{$table_name}.{$columns}" ] = $message;
		return $results;
	}

	/**
	 * Adds foreign key constraints to a table if they don't already exist.
	 *
	 * @since TBD
	 *
	 * @param string $table_name        The name of the table to add the foreign key to.
	 * @param string $foreign_key_name  The name of the foreign key constraint.
	 * @param string $column_name       The column that references the foreign key.
	 * @param string $referenced_table  The referenced table name.
	 * @param string $referenced_column The referenced column in the foreign table.
	 * @param string $on_delete_action  The action on delete (e.g., CASCADE).
	 *
	 * @return void
	 */
	protected function add_foreign_key(
		string $table_name,
		string $foreign_key_name,
		string $column_name,
		string $referenced_table,
		string $referenced_column,
		string $on_delete_action = 'CASCADE'
	) {
		global $wpdb;

		// Check if the foreign key already exists using `has_foreign_key`.
		if ( $this->has_foreign_key( $foreign_key_name, $table_name ) ) {
			return;
		}

		// Add the foreign key constraint if it doesn't exist.
		$sql = <<<SQL
ALTER TABLE `{$table_name}`
ADD CONSTRAINT `{$foreign_key_name}`
FOREIGN KEY (`{$column_name}`)
REFERENCES `{$referenced_table}`(`{$referenced_column}`)
ON DELETE {$on_delete_action}
SQL;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $sql );
	}

	/**
	 * Checks if a foreign key exists on a table.
	 *
	 * @since TBD
	 *
	 * @param string      $foreign_key The foreign key constraint name to check for.
	 * @param string|null $table_name The table name to check. Defaults to the current table.
	 *
	 * @return bool Whether the foreign key exists on the table.
	 */
	public function has_foreign_key( string $foreign_key, string $table_name = null ): bool {
		$table_name = $table_name ? : static::table_name();

		// Check in statistics (for indexes).
		$count_for_statistics = $this->db::table( $this->db::raw( 'information_schema.statistics' ) )
			->whereRaw( 'WHERE TABLE_SCHEMA = DATABASE()' )
			->where( 'TABLE_NAME', $table_name )
			->where( 'INDEX_NAME', $foreign_key )
			->count();

		// Check in constraints (for foreign key constraints).
		$count_for_constraints = $this->db::table( $this->db::raw( 'information_schema.TABLE_CONSTRAINTS' ) )
			->whereRaw( 'WHERE TABLE_SCHEMA = DATABASE()' )
			->where( 'TABLE_NAME', $table_name )
			->where( 'CONSTRAINT_NAME', $foreign_key )
			->count();

		// Return true if foreign key exists in either the statistics or constraints table.
		return ( $count_for_statistics > 0 || $count_for_constraints > 0 );
	}
}
