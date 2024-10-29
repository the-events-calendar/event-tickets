<?php
/**
 * Provides an API over action-based logging.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

/**
 * Class Logging.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller;
 */
trait Logging {
	/**
	 * Logs an error message dispatching the `tribe_log` action.
	 *
	 * @since 5.16.0
	 *
	 * @param string $message The message to log.
	 * @param array  $context The context of the message.
	 *
	 * @return void The message is logged.
	 */
	protected function log_error( string $message, array $context ) {
		do_action(
			'tribe_log',
			'error',
			$message,
			$context
		);
	}
}
