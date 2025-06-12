<?php
/**
 * Exception when there is not enough stock
 *
 * @since 5.24.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Exceptions;

use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class NotEnoughStockException
 *
 * @since 5.24.0
 */
class NotEnoughStockException extends Exception {}
