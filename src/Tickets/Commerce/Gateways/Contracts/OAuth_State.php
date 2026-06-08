<?php
/**
 * Issues and verifies single-use OAuth "state" tokens for gateway connect flows.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Class OAuth_State.
 *
 * The token is a cryptographically-random secret generated when an administrator
 * starts a gateway connection and stored server-side. Because validity means
 * "matches a token this site issued" rather than "matches a value derived from
 * the current user", it can be verified on the unauthenticated OAuth return
 * without a guessable nonce and without locking out the returning admin.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
class OAuth_State {

	/**
	 * Prefix for the transient that stores an issued token.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const TRANSIENT_PREFIX = 'tec_tc_oauth_state_';

	/**
	 * How long an issued token stays valid: the OAuth round-trip window.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const TTL = 15 * MINUTE_IN_SECONDS;

	/**
	 * Issue a new single-use state token and remember it server-side.
	 *
	 * @since TBD
	 *
	 * @return string The token to hand to the gateway.
	 */
	public function issue(): string {
		$token = bin2hex( random_bytes( 32 ) );
		set_transient( $this->get_key( $token ), 1, static::TTL );

		return $token;
	}

	/**
	 * Verify and consume a state token.
	 *
	 * Returns true only for a token this site issued that has not expired or been
	 * used already. The token is deleted on success so it can never be replayed.
	 *
	 * @since TBD
	 *
	 * @param string $token The token returned by the gateway.
	 *
	 * @return bool Whether the token is valid.
	 */
	public function verify( string $token ): bool {
		if ( '' === $token ) {
			return false;
		}

		$key = $this->get_key( $token );

		if ( false === get_transient( $key ) ) {
			return false;
		}

		// Single use: burn it on the way in.
		delete_transient( $key );

		return true;
	}

	/**
	 * Build the storage key for a token.
	 *
	 * The token is hashed so the raw secret is never stored at rest.
	 *
	 * @since TBD
	 *
	 * @param string $token The token.
	 *
	 * @return string
	 */
	protected function get_key( string $token ): string {
		return static::TRANSIENT_PREFIX . hash( 'sha256', $token );
	}
}
