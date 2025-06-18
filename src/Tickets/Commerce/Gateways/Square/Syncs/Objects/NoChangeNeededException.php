<?php
/**
 * NoChangeNeededException for Square synchronization.
 *
 * This exception is thrown when a sync operation is attempted but no change
 * is needed because the data is already in the desired state.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class NoChangeNeededException
 *
 * Exception thrown when no synchronization change is needed.
 * Used to indicate that the current state already matches the desired state.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class NoChangeNeededException extends Exception {
	// Empty.
}
