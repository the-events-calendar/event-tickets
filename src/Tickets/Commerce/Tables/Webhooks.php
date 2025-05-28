<?php
/**
 * The Webhooks table schema.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Tables;
 */

namespace TEC\Tickets\Commerce\Tables;

use TEC\Common\Integrations\Custom_Table_Abstract as Table;
use TEC\Common\StellarWP\DB\DB;

/**
 * Webhooks table schema.
 *
 * The table is used to store the webhooks events.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Tables;
 */
class Webhooks extends Table {
	/**
	 * The schema version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.2';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_tickets_commerce_webhooks';

	/**
	 * The table group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_commerce';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-commerce-webhooks';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $uid_column = 'event_id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	public static function get_columns(): array {
		return [
			static::$uid_column,
			'order_id',
			'event_type',
			'event_data',
			'created_at',
			'processed_at',
		];
	}

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
	 * Add indexes after table creation.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $results A map of results in the format
	 *                                      returned by the `dbDelta` function.
	 *
	 * @return array<string,string> A map of results in the format returned by
	 *                              the `dbDelta` function.
	 */
	protected function after_update( array $results ) {
		$this->check_and_add_index( $results, 'order_id', 'order_id' );

		if ( $this->has_foreign_key( 'order_id_fk' ) ) {
			return $results;
		}

		$db_name = DB::get_var( 'SELECT DATABASE()' );

		$inno_db_has_foreign_key = DB::table( DB::raw( 'information_schema.INNODB_SYS_FOREIGN' ) )
			->where( 'ID', $db_name . '/order_id_fk' )
			->where( 'FOR_NAME', $db_name . '/' . self::table_name( true ) )
			->where( 'REF_NAME', $db_name . '/' . DB::prefix( 'posts' ) )
			->count() > 0;

		if ( $inno_db_has_foreign_key ) {
			return $results;
		}

		DB::query(
			DB::prepare(
				'ALTER TABLE %i ADD CONSTRAINT `order_id_fk` FOREIGN KEY (`order_id`) REFERENCES %i (`ID`) ON DELETE CASCADE ON UPDATE NO ACTION',
				self::table_name( true ),
				DB::prefix( 'posts' )
			)
		);

		return $results;
	}

	/**
	 * Delete old stale entries.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function delete_old_stale_entries(): void {
		DB::query(
			DB::prepare(
				'DELETE FROM %i WHERE processed_at is NULL and created_at < %s',
				self::table_name( true ),
				time() - DAY_IN_SECONDS
			)
		);
	}
}
