<?php
/**
 * Custom Tables Exception, thrown when an operation on the custom tables fails.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Exceptions;
 */

namespace TEC\Tickets\Flexible_Tickets\Exceptions;

/**
 * Class Custom_Tables_Exception.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Exceptions;
 */
class Custom_Tables_Exception extends \Exception {
	public const CAPACITY_RELATIONSHIP_MISSING = 1;
	public const CAPACITY_MISSING = 2;
}