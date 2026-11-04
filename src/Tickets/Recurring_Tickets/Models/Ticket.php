<?php
/**
 * The model of a Recurring Event Tickets row.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Models
 */

namespace TEC\Tickets\Recurring_Tickets\Models;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table as Table_Interface;
use TEC\Common\StellarWP\SchemaModels\SchemaModel;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;

/**
 * Class Ticket.
 *
 * One template ticket on one date, as stored in the Recurring Event Tickets table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Models
 */
final class Ticket extends SchemaModel {
	/**
	 * Returns the table the model is stored in.
	 *
	 * @since TBD
	 *
	 * @return Table_Interface The Recurring Event Tickets table.
	 */
	public static function getTableInterface(): Table_Interface {
		return tribe( Tickets::class );
	}
}
