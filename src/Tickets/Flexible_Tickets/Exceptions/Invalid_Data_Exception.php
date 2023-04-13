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
	public const CAPACITY_MODE_MISSING = 0;
	public const GLOBAL_STOCK_MODE_MISSING_EVENT_CAPACITY = 1;
	public const INVALID_CAPACITY_MODE = 2;
	public const CAPPED_STOCK_MODE_MISSING_EVENT_CAPACITY = 3;
	public const CAPPED_STOCK_MODE_MISSING_TICKET_CAPACITY = 4;
}