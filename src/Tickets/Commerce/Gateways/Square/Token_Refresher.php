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
		if ( ! $this->merchant->get_refresh_token() || $this->merchant->is_token_invalid() ) {
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

		if ( $this->merchant->is_token_invalid() ) {
			return false;
		}

		if ( $this->is_backing_off() ) {
			return false;
		}

		$expiration = $this->merchant->get_token_expiration();

		if ( null === $expiration ) {
			// Connected before the expiration was tracked: try occasionally until we learn a real one.
			$last_attempt = strtotime( (string) $this->merchant->get_token_status()['last_attempt_at'] );

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
		$status   = $this->merchant->get_token_status();
		$failures = (int) $status['failures'];

		if ( $failures < 1 ) {
			return false;
		}

		$last_attempt = strtotime( (string) $status['last_attempt_at'] );

		if ( false === $last_attempt ) {
			return false;
		}

		$backoff = min( 12 * HOUR_IN_SECONDS, HOUR_IN_SECONDS * ( 2 ** min( 4, $failures - 1 ) ) );

		return $last_attempt + $backoff > time();
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
			return false;
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

			$this->merchant->update_token_status(
				[ 'last_attempt_at' => Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT ) ]
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

		// The request was understood and turned down.
		if ( $code >= 400 && $code < 500 ) {
			return self::RESULT_PERMANENT;
		}

		return false === $this->who_dat->is_token_accepted( true ) ? self::RESULT_PERMANENT : self::RESULT_TRANSIENT;
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

		$this->merchant->delete_token_status();

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
	 * Marks the connection as unrecoverable.
	 *
	 * The credentials are deliberately kept so support can still see what is stored.
	 *
	 * @since TBD
	 *
	 * @param int    $code   The response code the refresh came back with.
	 * @param string $reason Why the refresh was attempted.
	 *
	 * @return void
	 */
	protected function record_permanent_failure( int $code, string $reason ): void {
		$this->merchant->update_token_status(
			[
				'invalid_at' => Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT ),
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
		$failures = (int) $this->merchant->get_token_status()['failures'];

		$this->merchant->update_token_status( [ 'failures' => $failures + 1 ] );

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

		try {
			$acquired = (bool) DB::query(
				DB::prepare(
					'INSERT IGNORE INTO %i ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )',
					$table,
					$key,
					(string) $now,
					'no'
				)
			);

			if ( ! $acquired ) {
				// A crash must not hold refreshes off forever.
				$acquired = (bool) DB::query(
					DB::prepare(
						'UPDATE %i SET option_value = %s WHERE option_name = %s AND CAST( option_value AS UNSIGNED ) < %d',
						$table,
						(string) $now,
						$key,
						$now - self::LOCK_TIMEOUT
					)
				);
			}
		} catch ( Throwable $e ) {
			return false;
		}

		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( $key, 'options' );

		return $acquired;
	}

	/**
	 * Releases the refresh lock.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function release_lock(): void {
		delete_option( $this->get_lock_option_key() );
	}
}
