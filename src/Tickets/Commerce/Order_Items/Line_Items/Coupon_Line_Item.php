<?php
/**
 * Converts coupon order items to Order Items table rows and back.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

/**
 * Class Coupon_Line_Item.
 *
 * A coupon's `sub_total` is negative. Its `price` is the coupon's amount, which is a percent for a percentage
 * coupon, so it stays in `extra` instead of a money column.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
final class Coupon_Line_Item extends Abstract_Line_Item_Type {
	/**
	 * Item keys stored in a column, and how their value is stored.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const FIELDS = [
		'type'         => 'string',
		'ticket_id'    => 'int',
		'event_id'     => 'int',
		'quantity'     => 'int',
		'sub_total'    => 'money',
		'coupon_id'    => 'int',
		'display_name' => 'string',
	];

	/**
	 * Item keys whose column has another name; the others use a column of the same name.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const COLUMNS = parent::COLUMNS + [
		'coupon_id' => 'modifier_id',
	];
}
