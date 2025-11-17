<?php
/**
 * The Seat Selection reservations table schema.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Tables;
 */

namespace TEC\Tickets\Seating\Tables;

use Exception;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Columns\Blob_Column;
use TEC\Common\StellarWP\Schema\Columns\Column_Types;
use TEC\Common\StellarWP\Schema\Columns\Boolean_Column;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Tickets\Seating\Logging;

/**
 * Class Sessions.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating\Tables;
 */
class Sessions extends Table {
	use Logging;

	/**
	 * The schema version.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '1.1.0';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_slr_sessions';

	/**
	 * The table group.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_slr';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-slr-sessions';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 5.16.0
	 *
	 * @var string
	 */
	protected static $uid_column = 'token';

	/**
	 * Removes all the expired sessions from the table.
	 *
	 * @since 5.16.0
	 *
	 * @return int The number of expired sessions removed.
	 */
	public static function remove_expired_sessions(): int {
		try {
			$query = DB::prepare(
				'DELETE FROM %i WHERE expiration < %d',
				self::table_name(),
				time()
			);

			return (int) DB::query( $query );
		} catch ( Exception $e ) {
			( new self() )->log_error(
				'Failed to remove expired sessions.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'error'  => $e->getMessage(),
				]
			);

			return 0;
		}
	}

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
				$columns[] = ( new String_Column( 'token' ) )->set_length( 150 )->set_is_primary_key( true );
				$columns[] = new Referenced_ID( 'object_id' );
				$columns[] = ( new Integer_Column( 'expiration' ) )->set_length( 11 )->set_signed( false );
				$columns[] = ( new Blob_Column( 'reservations' ) )->set_type( Column_Types::LONGBLOB );
				$columns[] = ( new Boolean_Column( 'expiration_lock' ) )->set_default( false );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * Returns the table creation SQL in the format supported
	 * by the `dbDelta` function.
	 *
	 * @since 5.16.0
	 *
	 * @return string The table creation SQL, in the format supported
	 *                by the `dbDelta` function.
	 */
	public function get_definition(): string {
		global $wpdb;
		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "
			CREATE TABLE `{$table_name}` (
				`token` varchar(150) NOT NULL,
				`object_id` bigint(20) NOT NULL,
				`expiration` int(11) NOT NULL,
				`reservations` longblob,
				`expiration_lock` boolean DEFAULT 0,
				PRIMARY KEY (`token`)
			) {$charset_collate};
		";
	}

	/**
	 * Insert or updates a new row in the table depending on the existence of the token.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token                The token to insert or update.
	 * @param int    $object_id            The object ID to insert or update.
	 * @param int    $expiration_timestamp The timestamp to set as the expiration date.
	 *
	 * @return bool|int The number of rows affected, or `false` on failure.
	 */
	public function insert_or_update( string $token, int $object_id, int $expiration_timestamp ) {

		try {
			$query = DB::prepare(
				'INSERT INTO %i (token, object_id, expiration) VALUES (%s, %d, %d)
					ON DUPLICATE KEY UPDATE object_id = %d',
				self::table_name(),
				$token,
				$object_id,
				$expiration_timestamp,
				$object_id
			);

			return DB::query( $query ) !== false;
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to upsert the session.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return false;
		}
	}

	/**
	 * Returns the number of seconds left in the timer for a given token.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The token to get the seconds left for.
	 *
	 * @return int The number of seconds left in the timer.
	 */
	public function get_seconds_left( $token ): int {

		try {
			$query      = DB::prepare(
				'SELECT expiration FROM %i WHERE token = %s',
				self::table_name(),
				$token
			);
			$expiration = DB::get_var( $query );
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to get the seconds left for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return 0;
		}

		if ( empty( $expiration ) ) {
			// Either the token is not found or the session has expired and was removed.
			return 0;
		}

		return $expiration - time();
	}

	/**
	 * Returns the list of reservations for a given token and object ID.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The token to get the reservations for.
	 *
	 * @return array<int,array{
	 *     reservation_id: string,
	 *     seat_type_id: string,
	 *     seat_label: string,
	 * }> The list of reservations for the given token.
	 */
	public function get_reservations_for_token( string $token ) {
		try {
			$query        = DB::prepare(
				'SELECT reservations FROM %i WHERE token = %s ',
				self::table_name(),
				$token
			);
			$reservations = DB::get_var( $query );
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to get the reservations for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return [];
		}

		if ( empty( $reservations ) ) {
			return [];
		}

		return (array) json_decode( $reservations, true );
	}

	/**
	 * Gets the reservation UUIDs for a given token.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The token to get the reservation UUIDs for.
	 *
	 * @return string[] The list of reservation UUIDs for the given token.
	 */
	public function get_reservation_uuids_for_token( string $token ): array {
		$token_reservations = $this->get_reservations_for_token( $token );

		if ( empty( $token_reservations ) ) {
			return [];
		}

		return array_reduce(
			$token_reservations,
			static fn( $carry, $reservation ) => array_merge(
				$carry,
				array_column( $reservation, 'reservation_id' )
			),
			[]
		);
	}

	/**
	 * Updates, replacing them, the reservations for a given token.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token        Temporary token to identify the reservations.
	 * @param array  $reservations { The list of reservations to replace the existing ones with.
	 *    @type string $reservation_id The reservation ID.
	 *    @type string $seat_type_id   The seat type ID.
	 *    @type string $seat_label     The seat label.
	 * }
	 *
	 * @return bool Whether the reservations were updated or not.
	 */
	public function update_reservations( string $token, array $reservations ): bool {
		if ( $reservations === $this->get_reservations_for_token( $token ) ) {
			return true;
		}

		$reservations_json = wp_json_encode( $reservations );

		if ( false === $reservations_json ) {
			return false;
		}

		try {
			/*
			* The UPDATE operation will return the number of updated rows.
			* A value of 0 means that the row was either not found, or it did not need to be updated.
			* We want to fail the update if the row did not exist in the first place.
			*/
			$exists = DB::get_var(
				DB::prepare(
					'SELECT token FROM %i WHERE token = %s',
					self::table_name(),
					$token
				)
			);

			if ( empty( $exists ) ) {
				return false;
			}

			/*
			 * The result of this query might be 0 to indicate that the row was not updated.
			 * We want to fail the update if the row was not updated.
			 */
			$updated = DB::update(
				self::table_name(),
				[ 'reservations' => $reservations_json ],
				[ 'token' => $token ],
				[ '%s' ],
				[ '%s' ]
			);

			if ( $updated > 0 ) {
				/**
				 * Fires after the reservations were updated for a given token.
				 *
				 * @since 5.16.0
				 *
				 * @param string $token        The token to update the reservations for.
				 * @param array  $reservations The list of reservations to update the existing ones with.
				 */
				do_action( 'tec_tickets_seating_reservations_updated', $token, $reservations );
			}

			return $updated !== false;
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to update the reservations for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);


			return false;
		}
	}

	/**
	 * Deletes all the sessions for a given token.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The token to delete the sessions for.
	 *
	 * @return bool Whether the sessions werer deleted or not.
	 */
	public function delete_token_session( string $token ): bool {
		try {
			$query = DB::prepare(
				'DELETE FROM %i WHERE token = %s',
				self::table_name(),
				$token
			);

			return DB::query( $query ) !== false;
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to delete the sessions for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return false;
		}
	}

	/**
	 * Clears the reservations for a given token.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The token to clear the reservations for.
	 *
	 * @return bool|false Whether the reservations were cleared or not.
	 */
	public function clear_token_reservations( string $token ) {
		try {
			$query = DB::prepare(
				"UPDATE %i SET reservations = '' WHERE token = %s",
				self::table_name(),
				$token
			);

			return DB::query( $query ) !== false;
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to clear the reservations for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return false;
		}
	}

	/**
	 * Updates a session expiration timestamp by its token.
	 *
	 * Note the expiration will not be updated if the lock is set.
	 *
	 * @since 5.17.0
	 *
	 * @param string $token     The token to update the session for.
	 * @param int    $timestamp The UNIX timestamp to update the expiration to.
	 * @param bool   $lock      Whether to lock the expiration timestamp or not. Locking the expiration timestamp
	 *                          will prevent further changes until unlocked.
	 *
	 * @return bool Whether the expiration timestamp was updated or not. If a lock is set previously to this call,
	 *              then the method will return `false`.
	 */
	public function set_token_expiration_timestamp( string $token, int $timestamp, bool $lock = false ) {
		if ( $this->is_locked( $token ) ) {
			return false;
		}

		try {
			$query = DB::prepare(
				'UPDATE %i SET expiration = %d, expiration_lock = %d WHERE token = %s AND expiration_lock = 0',
				self::table_name(),
				$timestamp,
				(int) $lock,
				$token
			);

			return DB::query( $query ) !== false;
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to update the expiration timestamp for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return false;
		}
	}

	/**
	 * Returns whether the expiration lock for a token is set or not.
	 *
	 * @since 5.17.0
	 *
	 * @param string $token The token to check the expiration lock for.
	 *
	 * @return bool Whether the expiration lock for a token is set or not.
	 */
	public function is_locked( string $token ): bool {
		try {
			$query = DB::prepare(
				'SELECT expiration_lock FROM %i WHERE token = %s',
				self::table_name(),
				$token
			);

			return (bool) DB::get_var( $query );
		} catch ( Exception $e ) {
			$this->log_error(
				'Failed to get the expiration lock status for the token.',
				[
					'source' => __METHOD__,
					'code'   => $e->getCode(),
					'token'  => $token,
					'error'  => $e->getMessage(),
				]
			);

			return false;
		}
	}
}
