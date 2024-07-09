<?php
/**
 * The Seat Selection reservations table schema.
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
	 * Removes all the expired sessions from the table.
	 *
	 * @since TBD
	 *
	 * @return int The number of expired sessions removed.
	 */
	public static function remove_expired_sessions(): int {
		$query = DB::prepare(
			'DELETE FROM %i WHERE expiration < %d',
			self::table_name(),
			time()
		);

		return (int) DB::query( $query );
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
				`object_id` bigint(20) NOT NULL,
				`expiration` int(11) NOT NULL,
				`reservations` longblob DEFAULT '',
				PRIMARY KEY (`token`)
			) {$charset_collate};
		";
	}

	/**
	 * Insert or updates a new row in the table depending on the existence of the token.
	 *
	 * @since TBD
	 *
	 * @param string $token                The token to insert or update.
	 * @param int    $object_id            The object ID to insert or update.
	 * @param int    $expiration_timestamp The timestamp to set as the expiration date.
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public function upsert( string $token, int $object_id, int $expiration_timestamp ) {
		$query = DB::prepare(
			'INSERT INTO %i (token, object_id, expiration) VALUES (%s, %d, %d)
				ON DUPLICATE KEY UPDATE object_id = %d, expiration = %d',
			self::table_name(),
			$token,
			$object_id,
			$expiration_timestamp,
			$object_id,
			$expiration_timestamp
		);

		return DB::query( $query ) !== false;
	}

	/**
	 * Returns the number of seconds left in the timer for a given token.
	 *
	 * @since TBD
	 *
	 * @param string $token The token to get the seconds left for.
	 *
	 * @return int The number of seconds left in the timer.
	 */
	public function get_seconds_left( $token ): int {
		$query = DB::prepare(
			'SELECT expiration FROM %i WHERE token = %s',
			self::table_name(),
			$token
		);

		$expiration = DB::get_var( $query );

		if ( empty( $expiration ) ) {
			// Either the token is not found or the session has expired and was removed.
			return 0;
		}

		return $expiration - time();
	}

	/**
	 * Returns the list of reservations for a given token and object ID.
	 *
	 * @since TBD
	 *
	 * @param string $token     The token to get the reservations for.
	 *
	 * @return string[] The list of reservations for the given object ID.
	 */
	public function get_reservations_for_token( string $token ) {
		$query = DB::prepare(
			'SELECT reservations FROM %i WHERE token = %s ',
			self::table_name(),
			$token
		);

		$reservations = DB::get_var( $query );

		if ( empty( $reservations ) ) {
			return [];
		}

		return (array) json_decode( $reservations, true );
	}

	/**
	 * Updates, replacing them, the reservations for a given token.
	 *
	 * @since TBD
	 *
	 * @param string $token        Temporary token to identify the reservations.
	 * @param array  $reservations The list of reservations to replace the existing ones with.
	 *
	 * @return bool Whether the reservations were updated or not.
	 */
	public function update_reservations( string $token, array $reservations ): bool {
		$reservations_json = wp_json_encode( $reservations );

		if ( false === $reservations_json ) {
			return false;
		}

		$query = DB::prepare(
			'UPDATE %i SET reservations = %s WHERE token = %s',
			self::table_name(),
			$reservations_json,
			$token
		);

		return DB::query( $query ) !== false;
	}

	/**
	 * Deletes all the sessions for a given token.
	 *
	 * @since TBD
	 *
	 * @param string $token The token to delete the sessions for.
	 *
	 * @return bool Whether the sessions werer deleted or not.
	 */
	public function delete_token_session( string $token ): bool {
		$query = DB::prepare(
			'DELETE FROM %i WHERE token = %s',
			self::table_name(),
			$token
		);

		return DB::query( $query ) !== false;
	}

	/**
	 * Clears the reservations for a given token.
	 *
	 * @since TBD
	 *
	 * @param string $token The token to clear the reservations for.
	 *
	 * @return bool Whether the reservations were cleared or not.
	 */
	public function clear_token_reservations( string $token ): bool {
		$query = DB::prepare(
			"UPDATE %i SET reservations = '' WHERE token = %s",
			self::table_name(),
			$token
		);

		return DB::query( $query ) !== false;
	}
}
