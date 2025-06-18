<?php
/**
 * SquareRateLimitedException for Square synchronization.
 *
 * This exception is thrown when Square returns a 429 error code.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Exception;
use Throwable;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class SquareRateLimitedException
 *
 * Exception thrown when Square returns a 429 error code.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class SquareRateLimitedException extends Exception {

	/**
	 * Constructor.
	 *
	 * @since 5.24.0
	 *
	 * @param string    $message The message.
	 * @param int       $code The code.
	 * @param Throwable $previous The previous exception.
	 */
	public function __construct( string $message = '', int $code = 0, ?Throwable $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->store_rate_limitted();
	}

	/**
	 * Store the rate limitted exception.
	 *
	 * @since 5.24.0
	 */
	private function store_rate_limitted(): void {
		$square_limited_data   = (array) tribe_get_option( 'square_rate_limited', [] );
		$square_limited_data[] = time();
		tribe_update_option( 'square_rate_limited', $square_limited_data );
	}
}
