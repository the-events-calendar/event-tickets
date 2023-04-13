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

	public const CAPACITIES_INSERT_ERROR = 0;
	public const CAPACITIES_RELATIONSHIPS_INSERT_ERROR = 1;
}