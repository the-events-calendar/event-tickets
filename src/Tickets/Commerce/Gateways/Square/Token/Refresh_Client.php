<?php
/**
 * Sends the Square access token refresh and reads Square's answer.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use WP_Error;

/**
 * Class Refresh_Client
 *
 * The endpoint answers a rejected refresh token with an HTML 500 rather than a machine readable error,
 * so the response alone often says nothing. Turning that into a verdict a caller can act on - and
 * corroborating it against Square when it is ambiguous - is this class's whole job.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */
final class Refresh_Client {
	/**
	 * How many failures must pile up before an uncorroborated rejection counts as final.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const PERSISTENT_FAILURES = 5;

	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * WhoDat instance.
	 *
	 * @since TBD
	 *
	 * @var WhoDat
	 */
	private WhoDat $who_dat;

	/**
	 * Refresh status instance.
	 *
	 * @since TBD
	 *
	 * @var Refresh_Status
	 */
	private Refresh_Status $status;

	/**
	 * Refresh_Client constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant       $merchant Merchant instance.
	 * @param WhoDat         $who_dat  WhoDat instance.
	 * @param Refresh_Status $status   Refresh status instance.
	 */
	public function __construct( Merchant $merchant, WhoDat $who_dat, Refresh_Status $status ) {
		$this->merchant = $merchant;
		$this->who_dat  = $who_dat;
		$this->status   = $status;
	}

	/**
	 * Asks Square to renew the credentials.
	 *
	 * @since TBD
	 *
	 * @return Refresh_Outcome The verdict Square's answer supports.
	 */
	public function refresh(): Refresh_Outcome {
		return $this->classify( $this->who_dat->request_token_refresh() );
	}

	/**
	 * Decides whether a refresh attempt failed for good or is worth trying again.
	 *
	 * Square's own view of the stored access token settles the ambiguous cases: if Square no longer
	 * accepts the token and it cannot be renewed, the connection really is finished. Anything less
	 * certain counts as transient, so that an outage never disconnects a working site.
	 *
	 * @since TBD
	 *
	 * @param array{code: int, body: mixed, error: ?WP_Error} $result The outcome of the refresh request.
	 *
	 * @return Refresh_Outcome Success carrying the new credentials, or the failure verdict and its code.
	 */
	private function classify( array $result ): Refresh_Outcome {
		$code = $result['code'];
		$body = $result['body'];

		if ( 200 === $code && is_array( $body ) && ! empty( $body['access_token'] ) && is_string( $body['access_token'] ) ) {
			return Refresh_Outcome::success( $body );
		}

		// The request never completed, so nothing has been established about the credentials.
		if ( $result['error'] instanceof WP_Error || 0 === $code ) {
			return Refresh_Outcome::transient( $code );
		}

		// An outright authorization refusal needs no corroboration.
		if ( 401 === $code || 403 === $code ) {
			return Refresh_Outcome::permanent( $code );
		}

		// Everything else, a rate limit or a gateway error included, has to be corroborated.
		return $this->is_rejection_final()
			? Refresh_Outcome::permanent( $code )
			: Refresh_Outcome::transient( $code );
	}

	/**
	 * Whether a refusal the response could not explain is worth acting on.
	 *
	 * Square's status endpoint reports on the *access* token, which has usually expired by the time a
	 * refresh runs, so on its own it cannot tell a revoked grant from an outage: it says UNAUTHORIZED
	 * either way. It only carries independent information while the access token is still inside its
	 * own lifetime. When it does not, the connection is written off only after failures that keep
	 * repeating over more than a day, which an outage does not do.
	 *
	 * @since TBD
	 *
	 * @return bool True when the connection should be written off rather than retried.
	 */
	private function is_rejection_final(): bool {
		// Capped like the refresh itself: this runs on the same checkout request the refresh came from.
		$status = $this->who_dat->get_fresh_token_status( [ 'timeout' => WhoDat::TOKEN_REQUEST_TIMEOUT ] );

		if ( false !== $this->who_dat->interpret_token_status( $status ) ) {
			return false;
		}

		$expiration = $this->merchant->get_token_expiration();

		/*
		 * Square refusing a token that is still inside its own lifetime means the grant itself is gone.
		 * An expiration we never recorded proves nothing either way, so it does not count here.
		 */
		if ( null !== $expiration && $expiration->getTimestamp() > time() ) {
			return true;
		}

		$first_failure = $this->status->get_first_failure_timestamp();

		return $this->status->get_failure_count() >= self::PERSISTENT_FAILURES
			&& null !== $first_failure
			&& $first_failure <= time() - DAY_IN_SECONDS;
	}
}
