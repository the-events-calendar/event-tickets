<?php
/**
 * Invalid Data Exception, thrown when the provided data is not valid.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Exceptions;
 */

namespace TEC\Tickets\Flexible_Tickets\Exceptions;

/**
 * Class Invalid_Data_Exception.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets\Exceptions;
 */
class Invalid_Data_Exception extends \Exception {
	public const CAPACITY_VALUE_MISSING = 1;
	public const EVENT_CAPACITY_VALUE_MISSING = 2;
	public const CAPACITY_MODE_INVALID = 3;
}