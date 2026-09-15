<?php
/**
 * Decides when the Square access token should be refreshed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

use TEC\Tickets\Commerce\Gateways\Square\Merchant;

/**
 * Class Refresh_Policy
 *
 * Answers "should this run now?" from the stored credentials and the refresh history alone: no
 * network, no writes, no locking.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */
final class Refresh_Policy {
	/**
	 * How long before its expiration the access token is refreshed, in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const REFRESH_WINDOW = DAY_IN_SECONDS;

	/**
	 * How often a connection with no recorded expiration retries, in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const UNKNOWN_EXPIRATION_INTERVAL = 12 * HOUR_IN_SECONDS;

	/**
	 * How long a rejected connection waits before it asks Square again, in seconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const INVALID_RECHECK_INTERVAL = 12 * HOUR_IN_SECONDS;

	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Refresh status instance.
	 *
	 * @since TBD
	 *
	 * @var Refresh_Status
	 */
	private Refresh_Status $status;

	/**
	 * Refresh_Policy constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant       $merchant Merchant instance.
	 * @param Refresh_Status $status   Refresh status instance.
	 */
	public function __construct( Merchant $merchant, Refresh_Status $status ) {
		$this->merchant = $merchant;
		$this->status   = $status;
	}

	/**
	 * Whether a refresh is due.
	 *
	 * @since TBD
	 *
	 * @return bool True when the token is close to expiring, or a rejected connection is due a re-check.
	 */
	public function should_refresh(): bool {
		if ( ! $this->merchant->get_access_token() || ! $this->merchant->get_refresh_token() ) {
			return false;
		}

		if ( $this->is_backing_off() ) {
			return false;
		}

		// A rejected connection is retried rarely rather than never, so a wrong verdict heals itself.
		if ( $this->status->is_invalid() ) {
			return true;
		}

		$expiration = $this->merchant->get_token_expiration();

		if ( null === $expiration ) {
			// Connected before the expiration was tracked: try occasionally until we learn a real one.
			$last_attempt = $this->status->get_last_attempt_timestamp();

			return null === $last_attempt || $last_attempt < time() - self::UNKNOWN_EXPIRATION_INTERVAL;
		}

		return $expiration->getTimestamp() - self::REFRESH_WINDOW <= time();
	}

	/**
	 * Whether repeated transient failures are currently holding refreshes off.
	 *
	 * Keeps a WhoDat outage from turning into an outbound request on every page load.
	 *
	 * @since TBD
	 *
	 * @return bool True while the back-off window opened by the last failure is still running.
	 */
	public function is_backing_off(): bool {
		$failures = $this->status->get_failure_count();
		$invalid  = $this->status->is_invalid();

		if ( $failures < 1 && ! $invalid ) {
			return false;
		}

		$last_attempt = $this->status->get_last_attempt_timestamp();

		if ( null === $last_attempt ) {
			return false;
		}

		$backoff = $invalid
			? self::INVALID_RECHECK_INTERVAL
			: min( 12 * HOUR_IN_SECONDS, HOUR_IN_SECONDS * ( 2 ** min( 4, $failures - 1 ) ) );

		return $last_attempt + $backoff > time();
	}
}
