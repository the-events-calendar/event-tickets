<?php
/**
 * Exception for REST API related issues. Can also be used to convert errors
 * into WP_Error objects.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Exceptions
 */

namespace TEC\Tickets\Exceptions;

use Exception;
use WP_Error;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class RESTException
 *
 * @since TBD
 */
class RESTException extends Exception {
	/**
	 * The unique error code for the exception.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private string $error_code;

	/**
	 * The HTTP status code to return with the error.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $status_code;

	/**
	 * Constructs a new RESTException.
	 *
	 * @since TBD
	 *
	 * @param string $error_code  The error code for the exception.
	 * @param string $message     An optional message for the exception.
	 * @param int    $status_code The HTTP status code to return with the error.
	 */
	public function __construct( string $error_code, string $message = '', int $status_code = 400 ) {
		parent::__construct( $message );

		$this->error_code  = $error_code;
		$this->status_code = $status_code;
	}

	/**
	 * Gets the error code for the exception.
	 *
	 * @since TBD
	 *
	 * @return WP_Error The error code.
	 */
	public function to_wp_error(): WP_Error {
		return new WP_Error(
			$this->error_code,
			$this->getMessage(),
			[
				'status' => $this->status_code,
			]
		);
	}
}
