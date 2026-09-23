<?php
/**
 * The Order Item model.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Models
 */

namespace TEC\Tickets\Commerce\Order_Items\Models;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table as Table_Interface;
use TEC\Common\StellarWP\SchemaModels\SchemaModel;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items;

/**
 * Class Order_Item.
 *
 * One line of an order, as stored in the Order Items table.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Models
 */
class Order_Item extends SchemaModel {
	/**
	 * Returns the table the model is stored in.
	 *
	 * @since TBD
	 *
	 * @return Table_Interface The Order Items table.
	 */
	public static function getTableInterface(): Table_Interface {
		return tribe( Order_Items::class );
	}
}
