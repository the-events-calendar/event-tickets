<?php
/**
 * The Integrity_Table table schema.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs;
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Boolean_Column;
use TEC\Common\StellarWP\Schema\Columns\Created_At;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

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
	 * Returns the schema history for this table.
	 *
	 * @since 5.27.0
	 *
	 * @return array<string, callable>
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();
		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = ( new String_Column( 'square_object_id' ) )->set_length( 128 )->set_is_index( true );
				$columns[] = ( new String_Column( 'wp_object_id' ) )->set_length( 20 )->set_is_index( true );
				$columns[] = ( new String_Column( 'square_object_hash' ) )->set_length( 128 )->set_is_index( true );
				$columns[] = ( new Boolean_Column( 'mode' ) )->set_default( false );
				$columns[] = new Created_At( 'last_checked' );

				return new Table_Schema( $table_name, $columns );
			},
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
	public function get_definition(): string {
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
}
