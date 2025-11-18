<?php
/**
 * The Webhooks table schema.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Tables;
 */

namespace TEC\Tickets\Commerce\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Columns\Datetime_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Columns\Created_At;

/**
 * Webhooks table schema.
 *
 * The table is used to store the webhooks events.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Tables;
 */
class Webhooks extends Table {
	/**
	 * The schema version.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.2';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_tickets_commerce_webhooks';

	/**
	 * The table group.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_commerce';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-commerce-webhooks';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	protected static $uid_column = 'event_id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since 5.27.0
	 *
	 * @var string[]
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = ( new String_Column( 'event_id' ) )->set_length( 128 )->set_is_primary_key( true );
				$columns[] = ( new Referenced_ID( 'order_id' ) )->set_nullable( true );
				$columns[] = ( new String_Column( 'event_type' ) )->set_length( 128 )->set_is_index( true );
				$columns[] = ( new Text_Column( 'event_data' ) );
				$columns[] = ( new Created_At( 'created_at' ) )->set_nullable( true );
				$columns[] = ( new Datetime_Column( 'processed_at' ) )->set_nullable( true );

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
				`{$uid_column}` varchar(128) NOT NULL,
				`order_id` bigint(20) UNSIGNED NULL,
				`event_type` varchar(128) NOT NULL,
				`event_data` text NOT NULL,
				`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				`processed_at` timestamp NULL,
				PRIMARY KEY (`{$uid_column}`)
			) {$charset_collate};
		";
	}

	/**
	 * Delete old stale entries.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public static function delete_old_stale_entries(): void {
		DB::query(
			DB::prepare(
				'DELETE FROM %i WHERE processed_at is NULL and created_at < %s',
				self::table_name( true ),
				gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS )
			)
		);
	}
}
