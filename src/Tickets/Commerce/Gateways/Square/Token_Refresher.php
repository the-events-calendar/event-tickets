<?php
/**
 * Keeps the Square OAuth access token alive.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Square\Token\Refresh_Client;
use TEC\Tickets\Commerce\Gateways\Square\Token\Refresh_Lock;
use TEC\Tickets\Commerce\Gateways\Square\Token\Refresh_Outcome;
use TEC\Tickets\Commerce\Gateways\Square\Token\Refresh_Policy;
use TEC\Tickets\Commerce\Gateways\Square\Token\Refresh_Status;

/**
 * Class Token_Refresher
 *
 * Square access tokens expire roughly 30 days after they are minted. The refresh runs from the request
 * path rather than from a scheduled action so that a site with a stalled cron queue still recovers.
 *
 * This class orders the work and announces what came of it; when to run is Refresh_Policy's, running
 * exactly once across processes is Refresh_Lock's, talking to Square is Refresh_Client's, and what is
 * remembered afterwards is Refresh_Status's.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
final class Token_Refresher {
	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * Refresh policy instance.
	 *
	 * @since TBD
	 *
	 * @var Refresh_Policy
	 */
	private Refresh_Policy $policy;

	/**
	 * Refresh lock instance.
	 *
	 * @since TBD
	 *
	 * @var Refresh_Lock
	 */
	private Refresh_Lock $lock;

	/**
	 * Refresh client instance.
	 *
	 * @since TBD
	 *
	 * @var Refresh_Client
	 */
	private Refresh_Client $client;

	/**
	 * Refresh status instance.
	 *
	 * @since TBD
	 *
	 * @var Refresh_Status
	 */
	private Refresh_Status $status;

	/**
	 * Guards against a refresh re-entering itself within the same process.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $refreshing = false;

	/**
	 * Token_Refresher constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant       $merchant Merchant instance.
	 * @param Refresh_Policy $policy   Refresh policy instance.
	 * @param Refresh_Lock   $lock     Refresh lock instance.
	 * @param Refresh_Client $client   Refresh client instance.
	 * @param Refresh_Status $status   Refresh status instance.
	 */
	public function __construct(
		Merchant $merchant,
		Refresh_Policy $policy,
		Refresh_Lock $lock,
		Refresh_Client $client,
		Refresh_Status $status
	) {
		$this->merchant = $merchant;
		$this->policy   = $policy;
		$this->lock     = $lock;
		$this->client   = $client;
		$this->status   = $status;
	}

	/**
	 * Refreshes the access token when it is close enough to expiring.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the stored credentials are known to be current.
	 */
	public function refresh_if_needed(): bool {
		if ( ! $this->policy->should_refresh() ) {
			return false;
		}

		return $this->do_refresh( 'expiring' );
	}

	/**
	 * Refreshes the access token regardless of its recorded expiration.
	 *
	 * Used when Square itself rejects the token, which can happen before the recorded expiration.
	 *
	 * @since TBD
	 *
	 * @param string $reason         Why the refresh was forced, for logging.
	 * @param bool   $ignore_backoff Whether to attempt the refresh even inside the back-off window.
	 *
	 * @return bool Whether the stored access token is now different from the rejected one.
	 */
	public function refresh_now( string $reason = 'forced', bool $ignore_backoff = false ): bool {
		if ( ! $this->merchant->get_refresh_token() ) {
			return false;
		}

		// Without this a connection Square keeps rejecting would refresh once per Square API call.
		if ( ! $ignore_backoff && $this->policy->is_backing_off() ) {
			return false;
		}

		return $this->do_refresh( $reason, true );
	}

	/**
	 * Performs the refresh under the cross-process lock.
	 *
	 * @since TBD
	 *
	 * @param string $reason Why the refresh was attempted, for logging.
	 * @param bool   $forced Whether the recorded expiration was ignored.
	 *
	 * @return bool Whether the stored credentials are known to be current after this attempt.
	 */
	private function do_refresh( string $reason, bool $forced = false ): bool {
		if ( $this->refreshing ) {
			return false;
		}

		$previous_token = $this->merchant->get_access_token();

		if ( ! $this->lock->acquire() ) {
			/*
			 * Only a caller whose request already failed waits; a proactive refresh has nothing to gain
			 * from holding a page load open while somebody else does the work.
			 */
			return $forced && $this->lock->wait_for_holder( $previous_token );
		}

		$this->refreshing = true;

		try {
			// Whoever held the lock may have already done the work.
			$this->merchant->flush_option_cache();
			$this->status->flush_cache();

			if ( $forced ) {
				if ( $this->merchant->get_access_token() !== $previous_token ) {
					return true;
				}
			} elseif ( ! $this->policy->should_refresh() ) {
				return true;
			}

			$outcome = $this->client->refresh();

			$this->status->record_attempt();

			if ( $outcome->is_success() ) {
				return $this->record_success( $outcome, $reason );
			}

			if ( $outcome->is_permanent() ) {
				$this->record_permanent_failure( $outcome->get_code(), $reason );

				return false;
			}

			$this->record_transient_failure( $outcome->get_code(), $reason );

			return false;
		} finally {
			$this->refreshing = false;
			$this->lock->release();
		}
	}

	/**
	 * Stores the refreshed credentials and clears the failure bookkeeping.
	 *
	 * @since TBD
	 *
	 * @param Refresh_Outcome $outcome The successful outcome.
	 * @param string          $reason  Why the refresh was attempted.
	 *
	 * @return bool Whether the refreshed credentials were stored.
	 */
	private function record_success( Refresh_Outcome $outcome, string $reason ): bool {
		if ( ! $this->merchant->save_refreshed_tokens( $outcome->get_credentials() ) ) {
			$this->record_transient_failure( $outcome->get_code(), $reason );

			return false;
		}

		$this->status->record_success();

		return true;
	}

	/**
	 * Marks the connection as unavailable.
	 *
	 * The credentials are deliberately kept, both so support can still see what is stored and so the
	 * occasional re-check can lift the flag if Square starts renewing them again.
	 *
	 * @since TBD
	 *
	 * @param int    $code   The response code the refresh came back with.
	 * @param string $reason Why the refresh was attempted.
	 *
	 * @return void
	 */
	private function record_permanent_failure( int $code, string $reason ): void {
		$this->status->record_permanent_failure( $code );

		do_action(
			'tribe_log',
			'error',
			'Square rejected the access token refresh',
			[
				'source'        => 'tickets-commerce-square',
				'response_code' => $code,
				'reason'        => $reason,
			]
		);
	}

	/**
	 * Counts a failure that may well clear up on its own.
	 *
	 * @since TBD
	 *
	 * @param int    $code   The response code the refresh came back with, 0 when it never completed.
	 * @param string $reason Why the refresh was attempted.
	 *
	 * @return void
	 */
	private function record_transient_failure( int $code, string $reason ): void {
		$failures = $this->status->record_transient_failure();

		do_action(
			'tribe_log',
			'warning',
			'Square access token refresh did not go through',
			[
				'source'        => 'tickets-commerce-square',
				'response_code' => $code,
				'reason'        => $reason,
				'failures'      => $failures,
			]
		);
	}
}
