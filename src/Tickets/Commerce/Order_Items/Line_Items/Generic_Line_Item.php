<?php
/**
 * Converts the order items of a type with no line item type class of its own.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

/**
 * Class Generic_Line_Item.
 *
 * Stores the common columns and keeps every other key in `extra`, so an item added by another plugin round trips
 * without the line item types knowing it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
final class Generic_Line_Item extends Abstract_Line_Item_Type {
	/**
	 * Item keys stored in a column, and how their value is stored.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const FIELDS = parent::FIELDS + [
		'display_name' => 'string',
	];
}
