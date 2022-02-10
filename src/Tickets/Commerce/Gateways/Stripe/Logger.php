<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

/**
 * Class Logger for Stripe errors
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe;
 */
class Logger {

	/**
	 * Maximum number of error log entries to keep
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	const MAX_LOG_SIZE = 200;

	/**
	 * How long should we keep logs entries for
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	const LOG_EXPIRES_IN = DAY_IN_SECONDS;

	/**
	 * Option name for the error log option
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $stripe_error_log_option = 'tickets-commerce-stripe-error-log';

	/**
	 * Gets the proper log name to use with the given reference
	 *
	 * @since TBD
	 *
	 * @param string $reference a cart hash or another unique identifier
	 *
	 * @return string
	 */
	public static function get_log_name( $reference ) {
		return static::$stripe_error_log_option . '-' . $reference;
	}

	/**
	 * Retrieves log entries of the reference and type defined
	 *
	 * @since TBD
	 *
	 * @param string $reference a cart hash or another unique identifier
	 * @param string $type      the type of log entries to retrieve
	 *
	 * @return array
	 */
	public static function get( $reference, $type = 'all' ) {
		$logs = get_transient( static::get_log_name( $reference ) );

		if ( empty( $logs ) ) {
			return [];
		}

		if ( 'all' === $type ) {
			return (array) $logs;
		}

		return array_filter( (array) $logs, function ( $item ) use ( $reference, $type ) {
			return $item[ $type ] === $reference;
		} );
	}

	/**
	 * Save log to memory or the database
	 *
	 * @since TBD
	 *
	 * @param string $reference a cart hash or another unique identifier
	 * @param array  $log       the assembled log list
	 */
	public static function store( $reference, $log ) {
		set_transient( static::get_log_name( $reference ), $log, self::LOG_EXPIRES_IN );
	}

	/**
	 * Logs errors to the Stripe error log
	 *
	 * @since TBD
	 *
	 * @param string $reference a cart hash or another unique identifier
	 * @param array  $error     array of error information
	 */
	public static function log( $reference, $error ) {
		$error_log = static::get( $reference );

		$error_log[] = $error;

		$size = count( $error_log );

		// If we're over the max log size, roll over the older log entries.
		if ( $size > self::MAX_LOG_SIZE ) {
			$error_log = array_slice( $error_log, $size - self::MAX_LOG_SIZE );
		}

		static::store( $reference, $error_log );
	}

	/**
	 * Permanently deletes log entries
	 *
	 * @since TBD
	 *
	 * @param string $reference a cart hash or another unique identifier
	 *
	 * @return bool
	 */
	public static function delete( $reference ) {
		return delete_transient( static::get_log_name( $reference ) );
	}
}