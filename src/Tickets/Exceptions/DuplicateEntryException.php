<?php
/**
 * Exception when a duplicate entry is found
 *
 * @since 5.24.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Exceptions;

use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class DuplicateEntryException
 *
 * @since 5.24.0
 */
class DuplicateEntryException extends Exception {}
