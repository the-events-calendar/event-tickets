<?php
/**
 * NotSyncableItemException for Square synchronization.
 *
 * This exception is thrown when a sync checks against a ticket that is not syncable.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects;

use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class NotSyncableItemException
 *
 * Exception thrown when a sync checks against a ticket that is not syncable.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Syncs\Objects
 */
class NotSyncableItemException extends Exception {
	// Empty.
}
