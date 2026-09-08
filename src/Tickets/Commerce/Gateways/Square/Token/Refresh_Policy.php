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
 * network, no writes, no locking. Every interval it works from is filterable, and every one of those
 * filters is the reason a method here exists.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */
final class Refresh_Policy {
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

			return null === $last_attempt || $last_attempt < time() - $this->get_unknown_expiration_interval();
		}

		return $expiration->getTimestamp() - $this->get_refresh_window() <= time();
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
			? $this->get_invalid_recheck_interval()
			: min( 12 * HOUR_IN_SECONDS, HOUR_IN_SECONDS * ( 2 ** min( 4, $failures - 1 ) ) );

		return $last_attempt + $backoff > time();
	}

	/**
	 * How long before the expiration a refresh is attempted, in seconds.
	 *
	 * @since TBD
	 *
	 * @return int The window, in seconds.
	 */
	private function get_refresh_window(): int {
		/**
		 * Filters how long before its expiration the Square access token is refreshed.
		 *
		 * @since TBD
		 *
		 * @param int $window The window, in seconds.
		 */
		return absint( apply_filters( 'tec_tickets_commerce_square_token_refresh_window', DAY_IN_SECONDS ) );
	}

	/**
	 * How often a connection with no recorded expiration retries, in seconds.
	 *
	 * @since TBD
	 *
	 * @return int The interval, in seconds.
	 */
	private function get_unknown_expiration_interval(): int {
		/**
		 * Filters how often a Square connection with no recorded expiration attempts a refresh.
		 *
		 * @since TBD
		 *
		 * @param int $interval The interval, in seconds.
		 */
		return absint( apply_filters( 'tec_tickets_commerce_square_token_refresh_unknown_expiration_interval', 12 * HOUR_IN_SECONDS ) );
	}

	/**
	 * How long a rejected connection waits before it asks Square again.
	 *
	 * @since TBD
	 *
	 * @return int The interval, in seconds.
	 */
	private function get_invalid_recheck_interval(): int {
		/**
		 * Filters how often a rejected Square connection re-checks whether it can be renewed after all.
		 *
		 * @since TBD
		 *
		 * @param int $interval The interval, in seconds.
		 */
		return absint( apply_filters( 'tec_tickets_commerce_square_token_invalid_recheck_interval', 12 * HOUR_IN_SECONDS ) );
	}
}
