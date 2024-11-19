<?php
/**
 * Order Modifiers custom table logic.
 *
 * @package TEC\Tickets\Order_Modifiers\Custom_Tables;
 */

namespace TEC\Tickets\Order_Modifiers\Custom_Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use wpdb;

/**
 * Class Orders_Modifiers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Custom_Tables;
 */
class Order_Modifiers extends Table {
	/**
	 * @since TBD
	 *
	 * @var string|null The version number for this schema definition.
	 */
	public const SCHEMA_VERSION = '1.0.0';

	/**
	 * @since TBD
	 *
	 * @var string The base table name.
	 */
	protected static $base_table_name = 'tec_order_modifiers';

	/**
	 * @since TBD
	 *
	 * @var string The organizational group this table belongs to.
	 */
	protected static $group = 'tec_order_modifiers_group';

	/**
	 * @since TBD
	 *
	 * @var string|null The slug used to identify the custom table.
	 */
	protected static $schema_slug = 'tec-order-modifiers';

	/**
	 * @since TBD
	 *
	 * @var string The field that uniquely identifies a row in the table.
	 */
	protected static $uid_column = 'id';

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since TBD
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `$table_name` (
				`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				`modifier_type` VARCHAR(255) NOT NULL,
				`sub_type` VARCHAR(255) NOT NULL,
				`fee_amount_cents` INT NOT NULL,
				`slug` VARCHAR(255) NOT NULL,
				`display_name` VARCHAR(255) NOT NULL,
				`status` VARCHAR(20) NOT NULL DEFAULT 'draft',
				`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`start_time` TIMESTAMP NULL DEFAULT NULL,
				`end_time` TIMESTAMP NULL DEFAULT NULL,
				`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			) $charset_collate;
		";
	}

	/**
	 * Allows extending classes that require it to run some methods
	 * immediately after the table creation or update.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $results A map of results in the format
	 *                                      returned by the `dbDelta` function.
	 *
	 * @return array<string,string> A map of results in the format returned by
	 *                              the `dbDelta` function.
	 */
	protected function after_update( array $results ): array {
		// If nothing was changed by dbDelta(), bail.
		if ( ! count( $results ) ) {
			return $results;
		}

		global $wpdb;
		$table_name = self::table_name( true );

		// Check if the table exists first.
		if ( ! $this->exists() ) {
			return $results;
		}

		// Helper method to check and add indexes.
		$results = $this->check_and_add_index( $wpdb, $results, $table_name, 'tec_order_modifier_indx_slug', 'slug' );
		$results = $this->check_and_add_index( $wpdb, $results, $table_name, 'tec_order_modifier_indx_status_modifier_type_slug', 'status, modifier_type, slug' );
		$results = $this->check_and_add_index( $wpdb, $results, $table_name, 'tec_order_modifier_indx_type_display_name', 'modifier_type, display_name' );

		return $results;
	}

	/**
	 * Helper method to check and add an index to a table.
	 *
	 * @since TBD
	 *
	 * @param wpdb   $wpdb The WordPress database global.
	 * @param array  $results The results array to track changes.
	 * @param string $table_name The name of the table.
	 * @param string $index_name The name of the index.
	 * @param string $columns The columns to index.
	 *
	 * @return array The updated results array.
	 */
	protected function check_and_add_index( wpdb $wpdb, array $results, string $table_name, string $index_name, string $columns ): array {
		// Escape table name and columns for safety.
		$table_name = esc_sql( $table_name );
		$columns    = esc_sql( $columns );

		// Add index only if it does not exist.
		if ( ! $this->has_index( $index_name ) ) {
			// Prepare the SQL for adding an index.
			$sql = $wpdb->prepare(
				"ALTER TABLE `$table_name` ADD INDEX `%s` ( $columns )",
				$index_name
			);

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL.NotPrepared
			$updated = $wpdb->query( $sql );

			if ( $updated ) {
				$message = sprintf( 'Added index to the %s table on %s.', $table_name, $columns );
			} else {
				$message = sprintf( 'Failed to add an index on the %s table for %s.', $table_name, $columns );
			}

			$results[ "{$table_name}.{$columns}" ] = $message;
		}

		return $results;
	}
}
