<?php
/**
 * The Seat Selection Sessions table schema.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;

/**
 * Class Sessions.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Tables;
 */
class Sessions extends Table {
	/**
	 * The schema version.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_slr_sessions';

	/**
	 * The table group.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $group = 'tec_slr';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-slr-sessions';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $uid_column = 'token';

	/**
	 * Insert or updates a new row in the table depending on the existence of the token.
	 *
	 * @since TBD
	 *
	 * @param string $token                The token to insert or update.
	 * @param int    $post_id              The post ID to insert or update the session for.
	 * @param int    $expiration_timestamp The timestamp to set as the expiration date.
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public static function upsert( string $token, int $post_id, int $expiration_timestamp ) {
		$expiration_string = date( 'Y-m-d H:i:s', $expiration_timestamp );

		return DB::query(
				DB::prepare(
					"INSERT INTO %i ('token', 'post_id', 'expiration') VALUES (%s, %d, %s)
				ON DUPLICATE KEY UPDATE post_id = %s, expiration = %s",
					self::table_name(),
					$token,
					$post_id,
					$expiration_string,
					$post_id,
					$expiration_string
				)
			) !== false;
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

		return "
			CREATE TABLE `{$table_name}` (
				`token` varchar(255) NOT NULL,
				`post_id` varchar(255) NOT NULL,
				`expiration` DATETIME NOT NULL,
				PRIMARY KEY (`token`)
			) {$charset_collate};
		";
	}
}