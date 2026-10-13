<?php
/**
 * Logs messages of the Order Items feature.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Traits
 */

namespace TEC\Tickets\Commerce\Order_Items\Traits;

use TEC\Tickets\Commerce\Order_Items\Controller;

/**
 * Trait With_Logger.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Traits
 */
trait With_Logger {
	/**
	 * Logs a message, tagged with the Order Items controller.
	 *
	 * @since TBD
	 *
	 * @param string              $level   The log level.
	 * @param string              $message The message to log.
	 * @param array<string,mixed> $context The context to log with the message.
	 *
	 * @return void
	 */
	private function log( string $level, string $message, array $context ): void {
		do_action( 'tribe_log', $level, $message, [ 'controller' => Controller::class ] + $context );
	}
}
