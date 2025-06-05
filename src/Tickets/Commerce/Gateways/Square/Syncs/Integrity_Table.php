<?php
/**
 * The Integrity_Table table schema.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs;
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\Integrations\Custom_Table_Abstract as Table;

/**
 * Integrity_Table table schema.
 *
 * The table is used to check the integrity of local objects (Events and Tickets) and Square remote objects.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs;
 */
class Integrity_Table extends Table {
	/**
	 * The schema version.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.1-dev';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_square_sync_integrity';

	/**
	 * The table group.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_commerce_square';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-commerce-square-sync-integrity';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $uid_column = 'id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since 5.24.0
	 *
	 * @var string[]
	 */
	public static function get_columns(): array {
		return [
			static::$uid_column,
			'square_object_id',
			'wp_object_id',
			'square_object_hash',
			'mode',
			'last_checked',
		];
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since 5.24.0
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	protected function get_definition() {
		global $wpdb;
		$table_name      = self::table_name( true );
		$charset_collate = $wpdb->get_charset_collate();
		$uid_column      = self::uid_column();

		return "
			CREATE TABLE `{$table_name}` (
				`{$uid_column}` bigint(20) NOT NULL AUTO_INCREMENT,
				`square_object_id` varchar(128) NOT NULL,
				`wp_object_id` bigint(20) NOT NULL,
				`square_object_hash` varchar(128) NOT NULL,
				`mode` boolean NOT NULL DEFAULT 0,
				`last_checked` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				PRIMARY KEY (`{$uid_column}`)
			) {$charset_collate};
		";
	}

	/**
	 * Add indexes after table creation.
	 *
	 * @since 5.24.0
	 *
	 * @param array<string,string> $results A map of results in the format
	 *                                      returned by the `dbDelta` function.
	 *
	 * @return array<string,string> A map of results in the format returned by
	 *                              the `dbDelta` function.
	 */
	protected function after_update( array $results ) {
		$this->check_and_add_index( $results, 'square_object_id', 'square_object_id' );
		$this->check_and_add_index( $results, 'wp_object_id', 'wp_object_id' );

		return $results;
	}
}
