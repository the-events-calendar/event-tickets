<?php
/**
 * Serializes the Square access token refresh across concurrent requests.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Token;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Gateways\Square\Gateway;
use TEC\Tickets\Commerce\Gateways\Square\Merchant;
use TEC\Tickets\Commerce\Gateways\Square\WhoDat;
use Throwable;

/**
 * Class Refresh_Lock
 *
 * Two concurrent refreshes would each spend the refresh token, so only one may run. The lock lives in
 * the options table rather than in the object cache, because it has to hold across processes on a
 * site with no persistent cache.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Token
 */
final class Refresh_Lock {
	/**
	 * Seconds after which a held lock is considered abandoned.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const TIMEOUT = 60;

	/**
	 * How long to keep polling for the winner's result when the lock is contended, in microseconds.
	 *
	 * Has to outlast the holder's refresh call, or every waiter walks away before the new token lands
	 * and takes the rejection it was waiting to avoid. A second of headroom covers the writes around it.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const WAIT = ( WhoDat::TOKEN_REQUEST_TIMEOUT + 1 ) * 1000000;

	/**
	 * How often to re-read the credentials while waiting, in microseconds.
	 *
	 * Each poll is one indexed row read, so this only has to be fine enough to hand the waiter its
	 * token promptly once the holder writes it.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const POLL = 250000;

	/**
	 * Merchant instance.
	 *
	 * @since TBD
	 *
	 * @var Merchant
	 */
	private Merchant $merchant;

	/**
	 * The value written into the lock row while this process holds it.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $value = '';

	/**
	 * Refresh_Lock constructor.
	 *
	 * @since TBD
	 *
	 * @param Merchant $merchant Merchant instance.
	 */
	public function __construct( Merchant $merchant ) {
		$this->merchant = $merchant;
	}

	/**
	 * Takes the lock.
	 *
	 * INSERT IGNORE is atomic on the unique option_name index; add_option() reads before it writes and
	 * can be raced.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the lock is now held by this process.
	 */
	public function acquire(): bool {
		$key   = $this->get_option_key();
		$now   = time();
		$table = DB::prefix( 'options' );
		$value = $now . ':' . wp_generate_password( 12, false );

		try {
			$acquired = DB::query(
				DB::prepare(
					'INSERT IGNORE INTO %i ( option_name, option_value, autoload ) VALUES ( %s, %s, %s )',
					$table,
					$key,
					$value,
					'no'
				)
			) > 0;

			if ( ! $acquired ) {
				// A crash must not hold refreshes off forever.
				$acquired = DB::query(
					DB::prepare(
						'UPDATE %i SET option_value = %s WHERE option_name = %s AND CAST( SUBSTRING_INDEX( option_value, %s, 1 ) AS UNSIGNED ) < %d',
						$table,
						$value,
						$key,
						':',
						$now - self::TIMEOUT
					)
				) > 0;
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

		$this->value = $value;

		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( $key, 'options' );

		return true;
	}

	/**
	 * Releases the lock, but only while this process still holds it.
	 *
	 * A process that stalled past the timeout has had its lock taken over, and must not delete the row a
	 * second process is now working under.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function release(): void {
		if ( '' === $this->value ) {
			return;
		}

		try {
			DB::query(
				DB::prepare(
					'DELETE FROM %i WHERE option_name = %s AND option_value = %s',
					DB::prefix( 'options' ),
					$this->get_option_key(),
					$this->value
				)
			);
		} catch ( Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// The stale takeover will clear it.
		}

		$this->value = '';

		wp_cache_delete( $this->get_option_key(), 'options' );
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
	public function wait_for_holder( string $previous_token ): bool {
		$waited = 0;

		while ( $waited < self::WAIT ) {
			usleep( self::POLL );

			$waited += self::POLL;

			if ( $this->merchant->get_access_token_uncached() === $previous_token ) {
				continue;
			}

			$this->merchant->flush_option_cache();

			if ( $this->merchant->get_access_token() !== $previous_token ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The option name backing the lock.
	 *
	 * @since TBD
	 *
	 * @return string The option name the lock row is stored under.
	 */
	private function get_option_key(): string {
		$gateway_key = Gateway::get_key();
		$mode        = $this->merchant->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_token_refresh_lock_{$mode}";
	}
}
