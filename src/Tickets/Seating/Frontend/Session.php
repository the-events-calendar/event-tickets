<?php
/**
 * The Seating feature frontend session cookie.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Frontend;
 */

namespace TEC\Tickets\Seating\Frontend;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;

/**
 * Class Session.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating\Frontend;
 */
class Session {
	/**
	 * The cookie name used to store the ephemeral token.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const COOKIE_NAME = 'tec-tickets-seating-session';

	/**
	 * A reference to the Sessions table handler.
	 *
	 * @since TBD
	 *
	 * @var Sessions
	 */
	private Sessions $sessions;

	/**
	 * A reference to the Reservations object.
	 *
	 * @since TBD
	 *
	 * @var Reservations
	 */
	private Reservations $reservations;

	/**
	 * Session constructor.
	 *
	 * @since TBD
	 *
	 * @param Sessions     $sessions     A reference to the Sessions table handler.
	 * @param Reservations $reservations A reference to the Reservations object.
	 */
	public function __construct( Sessions $sessions, Reservations $reservations ) {
		$this->sessions     = $sessions;
		$this->reservations = $reservations;
	}

	/**
	 * Parses the cookie string into an array of object IDs and tokens.
	 *
	 * @since TBD
	 *
	 * @return array<string,string> The parsed cookie string, a map from object ID to token.
	 */
	public function get_entries(): array {
		$current = sanitize_text_field( $_COOKIE[ self::COOKIE_NAME ] ?? '' );
		$parsed  = [];
		foreach ( explode( '|||', $current ) as $entry ) {
			[ $object_id, $token ] = array_replace( [ '', '' ], explode( '=', $entry, 2 ) );
			if ( empty( $object_id ) || empty( $token ) ) {
				continue;
			}
			$parsed[ $object_id ] = $token;
		}

		return $parsed;
	}

	/**
	 * Returns the cookie string from the entries.
	 *
	 * @since TBD
	 *
	 * @param array<int,string> $entries The entries to convert to a cookie string.
	 *
	 * @return string The cookie string.
	 */
	public function get_cookie_string( array $entries ): string {
		return implode(
			'|||',
			array_map(
				static fn( $object_id, $token ) => $object_id . '=' . $token,
				array_keys( $entries ),
				$entries
			)
		);
	}

	/**
	 * Returns the filtered expiration time of the Seating session cookie.
	 *
	 * Note the cookie will contain multiple tokens, each one with a possibly different expiration time.
	 * For this reason, we set the cookie expiration time to 1 day, a value that should be large enough for any token
	 * contained in it to expire.
	 *
	 * @since TBD
	 *
	 * @return int The expiration time of the Seating session cookie.
	 */
	public function get_cookie_expiration_time(): int {
		/**
		 * Filters the expiration time of the Seating session cookie.
		 *
		 * @since TBD
		 *
		 * @param int $expiration_time The expiration time of the Seating session cookie.
		 */
		return apply_filters( 'tec_tickets_seating_session_cookie_expiration_time', DAY_IN_SECONDS );
	}

	/**
	 * Adds a new entry to the cookie string.
	 *
	 * @since TBD
	 *
	 * @param int    $object_id The object ID that will become the new entry key.
	 * @param string $token     The token that will become the new entry value.
	 */
	public function add_entry( int $object_id, string $token ): void {
		$entries               = $this->get_entries();
		$entries[ $object_id ] = $token;

		$new_value = $this->get_cookie_string( $entries );

		setcookie(
			self::COOKIE_NAME,
			$new_value,
			time() + $this->get_cookie_expiration_time(),
			COOKIEPATH,
			COOKIE_DOMAIN,
			true,
			true
		);
		$_COOKIE[ self::COOKIE_NAME ] = $new_value;
	}

	/**
	 * Removes an entry from the cookie.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id The post ID to remove the cookie entry for.
	 * @param string $token   The token to remove the cookie entry for.
	 *
	 * @return bool Also returns true.
	 */
	public function remove_entry( int $post_id, string $token ): bool {
		$entries = $this->get_entries();

		if ( isset( $entries[ $post_id ] ) && $entries[ $post_id ] === $token ) {
			unset( $entries[ $post_id ] );
		}

		$new_value = $this->get_cookie_string( $entries );

		if ( empty( $new_value ) ) {
			setcookie( self::COOKIE_NAME, '', time() - DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, true, true );
			unset( $_COOKIE[ self::COOKIE_NAME ] );
		} else {
			/*
			 * Cookies will store more than one token, each with, possibly, a different expiration time.
			 * We set the cookie expiration here to 1 day, to avoid the first expring token from removing it.
			 */
			setcookie(
				self::COOKIE_NAME,
				$new_value,
				time() + $this->get_cookie_expiration_time(),
				COOKIEPATH,
				COOKIE_DOMAIN,
				true,
				true
			);
			$_COOKIE[ self::COOKIE_NAME ] = $new_value;
		}

		return true;
	}

	/**
	 * Deletes the previous sessions reservations from the database.
	 *
	 * The token used for previous session reservations is read from the cookie.
	 *
	 * @since TBD
	 *
	 * @param int    $object_id The object ID to delete the sessions for.
	 * @param string $token     The token to cancel the previous session for.
	 *
	 * @return bool Whether the previous sessions were deleted or not.
	 */
	public function cancel_previous_for_object( int $object_id, string $token ): bool {
		if ( ! isset( $_COOKIE[ self::COOKIE_NAME ] ) ) {
			return true;
		}

		foreach ( $this->get_entries() as $entry_object_id => $cookie_token ) {
			if ( $entry_object_id !== $object_id ) {
				continue;
			}

			$reservations_uuids = $this->sessions->get_reservation_uuids_for_token( $cookie_token );

			if ( $token === $cookie_token ) {
				/*
				 * A new session with the same token, e.g. when the seat selection modal is opened again after
				 * closing it or cancelling the seat selection. Do not delete the token session, but cancel
				 * its previous reservations.
				 */
				return $this->reservations->cancel( $entry_object_id, $reservations_uuids )
						&& $this->sessions->clear_token_reservations( $token );
			}

			/*
			 * Start with a new token, e.g. on page reload where a new ephemeral token will be issued for the
			 * seat selection modal. Cancel the session and reservations for the previous token.
			 */
			return $this->reservations->cancel( $entry_object_id, $reservations_uuids )
					&& $this->sessions->delete_token_session( $cookie_token )
					&& $this->remove_entry( $entry_object_id, $cookie_token );
		}

		// Nothing to clear.
		return true;
	}

	/**
	 * Returns the token and object ID couple with the earliest expiration time from the cookie.
	 *
	 * @since TBD
	 *
	 * @param array<string,string> $cookie_entries The entries from the cookie. A map from object ID to token.
	 *
	 * @return array{0: string, 1: int}|null The token and object ID from the cookie, or `null` if not found.
	 */
	public function pick_earliest_expiring_token_object_id( array $cookie_entries ): ?array {
		$tokens_interval_p     = implode( ',', array_fill( 0, count( $cookie_entries ), '%s' ) );
		$object_ids_interval_p = implode( ',', array_fill( 0, count( $cookie_entries ), '%d' ) );
		$tokens_interval       = DB::prepare( $tokens_interval_p, ...array_values( $cookie_entries ) );
		$object_ids_interval   = DB::prepare( $object_ids_interval_p, ...array_keys( $cookie_entries ) );
		$query                 = DB::prepare(
			"SELECT object_id, token FROM %i WHERE token IN ({$tokens_interval}) AND object_id IN ({$object_ids_interval}) ORDER BY expiration ASC LIMIT 1",
			Sessions::table_name()
		);

		$earliest = DB::get_row( $query );

		if ( ! $earliest ) {
			return null;
		}

		return [ $earliest->token, $earliest->object_id ];
	}

	/**
	 * Returns the token and object ID relevant to set up the timer from the cookie.
	 *
	 * This method will apply a default logic found in the `default_token_object_id_handler` method
	 * to pick the object ID with the earliest expiration time.
	 * Extensions can modify this logic by filtering the `tec_tickets_seating_timer_token_object_id_handler` filter.
	 *
	 * @since TBD
	 *
	 * @return array{0: string, 1: int}|null The token and object ID from the cookie, or `null` if not found.
	 */
	public function get_session_token_object_id(): ?array {
		$entries = $this->get_entries();

		/**
		 * Filters the session entries used to get the token and object ID from the cookie for the purpose of
		 * tracking the seat selection session.
		 *
		 * @since TBD
		 *
		 * @param array<string,string> $entries The entries from the cookie. A map from object ID to token.
		 */
		$entries = apply_filters(
			'tec_tickets_seating_timer_token_object_id_entries',
			$entries,
		);

		if ( empty( $entries ) ) {
			return null;
		}

		/**
		 * Filters the handler used to get the token and object ID from the cookie.
		 * The default handler will pick the object ID and token couple with the earliest expiration time.
		 *
		 * @since TBD
		 *
		 * @param callable             $handler The handler used to get the token and object ID from the cookie.
		 * @param array<string,string> $entries The entries from the cookie. A map from object ID to token.
		 */
		$handler = apply_filters(
			'tec_tickets_seating_timer_token_object_id_handler',
			[ $this, 'pick_earliest_expiring_token_object_id' ]
		);

		[ $token, $object_id ] = array_replace( [ '', '' ], (array) $handler( $entries ) );

		return $token && $object_id ? [ $token, $object_id ] : null;
	}

	/**
	 * Confirms all the reservations contained in the cookie.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the reservations were confirmed or not.
	 */
	public function confirm_all_reservations(): bool {
		$confirmed = true;

		foreach ( $this->get_entries() as $post_id => $token ) {
			$reservation_uuids = $this->sessions->get_reservation_uuids_for_token( $token );

			if ( empty( $reservation_uuids ) ) {
				continue;
			}

			$confirmed &= $this->reservations->confirm( $post_id, $reservation_uuids )
							&& $this->sessions->delete_token_session( $token );
		}

		return $confirmed;
	}
}
