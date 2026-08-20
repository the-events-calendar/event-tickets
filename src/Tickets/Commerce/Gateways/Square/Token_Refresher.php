<?php
/**
 * Keeps the Square OAuth access token alive.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\StellarWP\DB\DB;
use Tribe__Date_Utils as Dates;
use Throwable;
use WP_Error;

/**
 * Class Token_Refresher
 *
 * Square access tokens expire roughly 30 days after they are minted. The refresh runs from the request
 * path rather than from a scheduled action so that a site with a stalled cron queue still recovers.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Token_Refresher {
	/**
	 * The refresh response carried a new access token.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RESULT_SUCCESS = 'success';

	/**
	 * Square refused the refresh token; only a new OAuth handshake can recover.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RESULT_PERMANENT = 'permanent';

	/**
	 * The refresh did not go through, but the credentials may still be good.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const RESULT_TRANSIENT = 'transient';

	/**
	 * Seconds after which a held refresh lock is considered abandoned.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const LOCK_TIMEOUT = 60;

	/**
	 * How long to keep polling for the winner's result when the lock is contended, in microseconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const LOCK_WAIT = 1000000;

	/**
	 * How often to re-read the credentials while waiting on a contended lock, in microseconds.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const LOCK_POLL = 100000;

	/**
	 * How many failures must pile up before an uncorroborated rejection counts as final.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	protected const PERSISTENT_FAILURES = 5;

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
	 * Guards against a refresh re-entering itself within the same process.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private bool $refreshing = false;

	/**
	 * The value written into the lock row while this process holds it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $lock_value = '';

	/**
	 * Token_Refresher constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant $merchant Merchant instance.
	 * @param WhoDat   $who_dat  WhoDat instance.
	 */
	public function __construct( Merchant $merchant, WhoDat $who_dat ) {
		$this->merchant = $merchant;
		$this->who_dat  = $who_dat;
	}

	/**
	 * Refreshes the access token when it is close enough to expiring.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the stored credentials are known to be current.
	 */
	public function refresh_if_needed(): bool {
		if ( ! $this->should_refresh() ) {
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
	 * @param string $reason Why the refresh was forced, for logging.
	 *
	 * @return bool Whether the stored access token is now different from the rejected one.
	 */
	public function refresh_now( string $reason = 'forced' ): bool {
		if ( ! $this->merchant->get_refresh_token() ) {
			return false;
		}

		// Without this a connection Square keeps rejecting would refresh once per Square API call.
		if ( $this->is_backing_off() ) {
			return false;
		}

		return $this->do_refresh( $reason, true );
	}

	/**
	 * Whether a refresh is due.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_refresh(): bool {
		if ( ! $this->merchant->get_access_token() || ! $this->merchant->get_refresh_token() ) {
			return false;
		}

		if ( $this->is_backing_off() ) {
			return false;
		}

		// A rejected connection is retried rarely rather than never, so a wrong verdict heals itself.
		if ( $this->merchant->is_token_invalid() ) {
			return true;
		}

		$expiration = $this->merchant->get_token_expiration();

		if ( null === $expiration ) {
			// Connected before the expiration was tracked: try occasionally until we learn a real one.
			$last_attempt = strtotime( (string) $this->merchant->get_refresh_status()['last_attempt_at'] );

			return false === $last_attempt || $last_attempt < time() - $this->get_unknown_expiration_interval();
		}

		return $expiration->getTimestamp() - $this->get_refresh_window() <= time();
	}

	/**
	 * How long before the expiration a refresh is attempted, in seconds.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function get_refresh_window(): int {
		/**
		 * Filters how long before its expiration the Square access token is refreshed.
		 *
		 * @since TBD
		 *
		 * @param int $window The window, in seconds.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_square_token_refresh_window', DAY_IN_SECONDS );
	}

	/**
	 * How often a connection with no recorded expiration retries, in seconds.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	protected function get_unknown_expiration_interval(): int {
		/**
		 * Filters how often a Square connection with no recorded expiration attempts a refresh.
		 *
		 * @since TBD
		 *
		 * @param int $interval The interval, in seconds.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_square_token_refresh_unknown_expiration_interval', 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Whether repeated transient failures are currently holding refreshes off.
	 *
	 * Keeps a WhoDat outage from turning into an outbound request on every page load.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	protected function is_backing_off(): bool {
		$status   = $this->merchant->get_refresh_status();
		$failures = (int) $status['failures'];
		$invalid  = ! empty( $status['invalid_at'] );

		if ( $failures < 1 && ! $invalid ) {
			return false;
		}

		$last_attempt = strtotime( (string) $status['last_attempt_at'] );

		if ( false === $last_attempt ) {
			return false;
		}

		$backoff = $invalid
			? $this->get_invalid_recheck_interval()
			: min( 12 * HOUR_IN_SECONDS, HOUR_IN_SECONDS * ( 2 ** min( 4, $failures - 1 ) ) );

		return $last_attempt + $backoff > time();
	}

	/**
	 * How long a rejected connection waits before it asks Square again.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	protected function get_invalid_recheck_interval(): int {
		/**
		 * Filters how often a rejected Square connection re-checks whether it can be renewed after all.
		 *
		 * @since TBD
		 *
		 * @param int $interval The interval, in seconds.
		 */
		return (int) apply_filters( 'tec_tickets_commerce_square_token_invalid_recheck_interval', 12 * HOUR_IN_SECONDS );
	}

	/**
	 * Performs the refresh under a cross-process lock.
	 *
	 * @since TBD
	 *
	 * @param string $reason Why the refresh was attempted, for logging.
	 * @param bool   $forced Whether the recorded expiration was ignored.
	 *
	 * @return bool
	 */
	protected function do_refresh( string $reason, bool $forced = false ): bool {
		if ( $this->refreshing ) {
			return false;
		}

		$previous_token = $this->merchant->get_access_token();

		if ( ! $this->create_lock() ) {
			// Only a caller whose request already failed waits; a proactive refresh has nothing to gain
			// from holding a page load open while somebody else does the work.
			return $forced && $this->wait_for_lock_holder( $previous_token );
		}

		$this->refreshing = true;

		try {
			// Whoever held the lock may have already done the work.
			$this->merchant->flush_option_cache();

			if ( $forced ) {
				if ( $this->merchant->get_access_token() !== $previous_token ) {
					return true;
				}
			} elseif ( ! $this->should_refresh() ) {
				return true;
			}

			$result = $this->who_dat->request_token_refresh();
			$status = $this->classify_result( $result );

			$this->merchant->update_refresh_status(
				[ 'last_attempt_at' => Dates::build_date_object( 'now', 'UTC' )->format( Dates::DBDATETIMEFORMAT ) ]
			);

			if ( self::RESULT_SUCCESS === $status ) {
				return $this->record_success( (array) $result['body'], $reason );
			}

			if ( self::RESULT_PERMANENT === $status ) {
				$this->record_permanent_failure( (int) $result['code'], $reason );

				return false;
			}

			$this->record_transient_failure( (int) $result['code'], $reason );

			return false;
		} finally {
			$this->refreshing = false;
			$this->release_lock();
		}
	}

	/**
	 * Decides whether a refresh attempt failed for good or is worth trying again.
	 *
	 * The endpoint answers a rejected refresh token with an HTML 500 rather than a machine readable
	 * error, so a 500 on its own says nothing. Square's own view of the stored access token settles it:
	 * if Square no longer accepts the token and it cannot be renewed, the connection really is finished.
	 * Anything less certain counts as transient, so that an outage never disconnects a working site.
	 *
	 * @since TBD
	 *
	 * @param array $result The outcome of the refresh request.
	 *
	 * @return string One of the RESULT_* constants.
	 */
	protected function classify_result( array $result ): string {
		$code = (int) $result['code'];
		$body = $result['body'];

		if ( 200 === $code && ! empty( $body['access_token'] ) && is_string( $body['access_token'] ) ) {
			return self::RESULT_SUCCESS;
		}

		// The request never completed, so nothing has been established about the credentials.
		if ( $result['error'] instanceof WP_Error || 0 === $code ) {
			return self::RESULT_TRANSIENT;
		}

		// An outright authorization refusal needs no corroboration.
		if ( 401 === $code || 403 === $code ) {
			return self::RESULT_PERMANENT;
		}

		// Everything else, a rate limit or a gateway error included, has to be corroborated.
		return $this->is_rejection_final() ? self::RESULT_PERMANENT : self::RESULT_TRANSIENT;
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
	 * @return bool
	 */
	protected function is_rejection_final(): bool {
		if ( false !== $this->who_dat->is_token_accepted( true ) ) {
			return false;
		}

		$expiration = $this->merchant->get_token_expiration();

		// Square refusing a token that is still inside its own lifetime means the grant itself is gone.
		// An expiration we never recorded proves nothing either way, so it does not count here.
		if ( null !== $expiration && $expiration->getTimestamp() > time() ) {
			return true;
		}

		$status        = $this->merchant->get_refresh_status();
		$first_failure = strtotime( (string) $status['first_failure_at'] );

		return (int) $status['failures'] >= self::PERSISTENT_FAILURES
			&& false !== $first_failure
			&& $first_failure <= time() - DAY_IN_SECONDS;
	}

	/**
	 * Stores the refreshed credentials and clears the failure bookkeeping.
	 *
	 * @since TBD
	 *
	 * @param array  $response The refresh response.
	 * @param string $reason   Why the refresh was attempted.
	 *
	 * @return bool
	 */
	protected function record_success( array $response, string $reason ): bool {
		if ( ! $this->merchant->save_refreshed_tokens( $response ) ) {
			$this->record_transient_failure( 200, $reason );

			return false;
		}

		// last_attempt_at survives: it is the only throttle a connection with no known expiration has.
		$this->merchant->update_refresh_status(
			[
				'invalid_at'       => '',
				'error'            => '',
				'failures'         => 0,
				'first_failure_at' => '',
			]
		);

		/**
		 * Fires after the Square access token has been refreshed.
		 *
		 * @since TBD
		 *
		 * @param string $reason Why the refresh was attempted.
		 */
		do_action( 'tec_tickets_commerce_square_access_token_refreshed', $reason );

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
	protected function record_permanent_failure( int $code, string $reason ): void {
		$this->merchant->update_refresh_status(
			[
				'invalid_at' => Dates::build_date_object( 'now', 'UTC' )->format( Dates::DBDATETIMEFORMAT ),
				'error'      => (string) $code,
			]
		);

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

		/**
		 * Fires when Square refuses to renew the stored credentials.
		 *
		 * @since TBD
		 *
		 * @param int    $code   The response code the refresh came back with.
		 * @param string $reason Why the refresh was attempted.
		 */
		do_action( 'tec_tickets_commerce_square_access_token_refresh_failed', $code, $reason );
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
	protected function record_transient_failure( int $code, string $reason ): void {
		$status   = $this->merchant->get_refresh_status();
		$failures = (int) $status['failures'] + 1;

		$this->merchant->update_refresh_status(
			[
				'failures'         => $failures,
				'first_failure_at' => $status['first_failure_at'] ?: Dates::build_date_object( 'now', 'UTC' )->format( Dates::DBDATETIMEFORMAT ),
			]
		);

		do_action(
			'tribe_log',
			'warning',
			'Square access token refresh did not go through',
			[
				'source'        => 'tickets-commerce-square',
				'response_code' => $code,
				'reason'        => $reason,
				'failures'      => $failures + 1,
			]
		);
	}

	/**
	 * The option name backing the refresh lock.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	protected function get_lock_option_key(): string {
		$gateway_key = Gateway::get_key();
		$mode        = $this->merchant->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_token_refresh_lock_{$mode}";
	}

	/**
	 * Takes the refresh lock.
	 *
	 * Two concurrent refreshes would each spend the refresh token, so only one may run. INSERT IGNORE is
	 * atomic on the unique option_name index; add_option() reads before it writes and can be raced.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the lock is now held by this process.
	 */
	protected function create_lock(): bool {
		$key   = $this->get_lock_option_key();
		$now   = time();
		$table = DB::prefix( 'options' );
		$value = $now . ':' . wp_generate_password( 12, false );

		try {
			$acquired = (bool) DB::query(
				DB::prepare(
					'INSERT IGNORE INTO %i ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )',
					$table,
					$key,
					$value,
					'no'
				)
			);

			if ( ! $acquired ) {
				// A crash must not hold refreshes off forever.
				$acquired = (bool) DB::query(
					DB::prepare(
						'UPDATE %i SET option_value = %s WHERE option_name = %s AND CAST( SUBSTRING_INDEX( option_value, %s, 1 ) AS UNSIGNED ) < %d',
						$table,
						$value,
						$key,
						':',
						$now - self::LOCK_TIMEOUT
					)
				);
			}
		} catch ( Throwable $e ) {
			// Left unlogged the refresh would simply stop happening, which is the failure being fixed.
			do_action(
				'tribe_log',
				'error',
				'Square access token refresh could not take its lock',
				[
					'source' => 'tickets-commerce-square',
					'error'  => $e->getMessage(),
				]
			);

			return false;
		}

		if ( ! $acquired ) {
			return false;
		}

		$this->lock_value = $value;

		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( $key, 'options' );

		return true;
	}

	/**
	 * Releases the refresh lock, but only while this process still holds it.
	 *
	 * A process that stalled past the lock timeout has had its lock taken over, and must not delete the
	 * row a second process is now working under.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function release_lock(): void {
		if ( '' === $this->lock_value ) {
			return;
		}

		try {
			DB::query(
				DB::prepare(
					'DELETE FROM %i WHERE option_name = %s AND option_value = %s',
					DB::prefix( 'options' ),
					$this->get_lock_option_key(),
					$this->lock_value
				)
			);
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// The stale takeover will clear it.
		}

		$this->lock_value = '';

		wp_cache_delete( $this->get_lock_option_key(), 'options' );
	}

	/**
	 * Waits out a refresh another process is already running.
	 *
	 * Without this every concurrent request that finds an expired token fails its Square call while the
	 * winner is still mid-refresh, which at checkout means a failed payment per shopper.
	 *
	 * @since TBD
	 *
	 * @param string $previous_token The access token this process started with.
	 *
	 * @return bool Whether the credentials were renewed by whoever held the lock.
	 */
	protected function wait_for_lock_holder( string $previous_token ): bool {
		$waited = 0;

		while ( $waited < self::LOCK_WAIT ) {
			usleep( self::LOCK_POLL );

			$waited += self::LOCK_POLL;

			$this->merchant->flush_option_cache();

			if ( $this->merchant->get_access_token() !== $previous_token ) {
				return true;
			}
		}

		return false;
	}
}
