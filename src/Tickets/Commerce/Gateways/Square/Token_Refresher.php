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

			$response = $this->who_dat->refresh_token();
			$result   = $this->classify_response( $response );

			$this->merchant->update_token_status(
				[ 'last_attempt_at' => Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT ) ]
			);

			if ( self::RESULT_SUCCESS === $result ) {
				return $this->record_success( $response, $reason );
			}

			if ( self::RESULT_PERMANENT === $result ) {
				$this->record_permanent_failure( $this->get_error_code( $response ), $reason );

				return false;
			}

			$this->record_transient_failure( $reason );

			return false;
		} finally {
			$this->refreshing = false;
			$this->release_lock();
		}
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
			$this->record_transient_failure( $reason );

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
	 * @param string $error  The error code Square returned.
	 * @param string $reason Why the refresh was attempted.
	 *
	 * @return void
	 */
	protected function record_permanent_failure( string $error, string $reason ): void {
		$this->merchant->update_token_status(
			[
				'invalid_at' => Dates::build_date_object()->format( Dates::DBDATETIMEFORMAT ),
				'error'      => $error,
			]
		);

		do_action(
			'tribe_log',
			'error',
			'Square rejected the access token refresh',
			[
				'source' => 'tickets-commerce-square',
				'error'  => $error,
				'reason' => $reason,
			]
		);

		/**
		 * Fires when Square refuses to renew the stored credentials.
		 *
		 * @since TBD
		 *
		 * @param string $error  The error code Square returned.
		 * @param string $reason Why the refresh was attempted.
		 */
		do_action( 'tec_tickets_commerce_square_access_token_refresh_failed', $error, $reason );
	}

	/**
	 * Counts a failure that may well clear up on its own.
	 *
	 * @since TBD
	 *
	 * @param string $reason Why the refresh was attempted.
	 *
	 * @return void
	 */
	protected function record_transient_failure( string $reason ): void {
		$failures = (int) $this->merchant->get_token_status()['failures'];

		$this->merchant->update_token_status( [ 'failures' => $failures + 1 ] );

		do_action(
			'tribe_log',
			'warning',
			'Square access token refresh did not go through',
			[
				'source'   => 'tickets-commerce-square',
				'reason'   => $reason,
				'failures' => $failures + 1,
			]
		);
	}

	/**
	 * Decides whether a refresh response is a success, an unrecoverable rejection, or a blip.
	 *
	 * Abstract_WhoDat::get() returns null both for transport errors and for bodies it cannot decode, and
	 * returns the decoded body whatever the status code, so the body is all there is to go on.
	 *
	 * @since TBD
	 *
	 * @param mixed $response The refresh response.
	 *
	 * @return string One of the RESULT_* constants.
	 */
	protected function classify_response( $response ): string {
		if ( ! is_array( $response ) ) {
			return self::RESULT_TRANSIENT;
		}

		if ( ! empty( $response['access_token'] ) && is_string( $response['access_token'] ) ) {
			return self::RESULT_SUCCESS;
		}

		$error = $this->get_error_code( $response );

		if ( '' === $error ) {
			return self::RESULT_TRANSIENT;
		}

		return in_array( $error, $this->get_permanent_error_codes(), true ) ? self::RESULT_PERMANENT : self::RESULT_TRANSIENT;
	}

	/**
	 * Pulls an error code out of either the OAuth or the Square error shape.
	 *
	 * @since TBD
	 *
	 * @param mixed $response The refresh response.
	 *
	 * @return string Lowercased error code, empty when there is none.
	 */
	protected function get_error_code( $response ): string {
		if ( ! is_array( $response ) ) {
			return '';
		}

		if ( ! empty( $response['error'] ) && is_string( $response['error'] ) ) {
			return strtolower( $response['error'] );
		}

		$error = $response['errors'][0] ?? null;

		if ( ! is_array( $error ) ) {
			return '';
		}

		foreach ( [ 'code', 'category' ] as $key ) {
			if ( ! empty( $error[ $key ] ) && is_string( $error[ $key ] ) ) {
				return strtolower( $error[ $key ] );
			}
		}

		return '';
	}

	/**
	 * The error codes that mean the refresh token will never work again.
	 *
	 * @since TBD
	 *
	 * @return string[]
	 */
	protected function get_permanent_error_codes(): array {
		$codes = [
			'access_denied',
			'authentication_error',
			'forbidden',
			'invalid_client',
			'invalid_grant',
			'refresh_token_expired',
			'refresh_token_revoked',
			'unauthorized',
			'unauthorized_client',
			'unsupported_grant_type',
		];

		/**
		 * Filters the Square token refresh errors treated as unrecoverable.
		 *
		 * Anything not listed here is treated as transient and leaves the connection alone.
		 *
		 * @since TBD
		 *
		 * @param string[] $codes Lowercased error codes.
		 */
		return (array) apply_filters( 'tec_tickets_commerce_square_permanent_token_errors', $codes );
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
