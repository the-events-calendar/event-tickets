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
 * A coupon's `sub_total` is negative.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
class Coupon_Line_Item extends Abstract_Line_Item_Type {
	/**
	 * Item keys stored in a column, and how their value is stored.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const FIELDS = parent::FIELDS + [
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

	/**
	 * Returns the item type this class converts.
	 *
	 * @since TBD
	 *
	 * @return string The item type.
	 */
	public function get_type(): string {
		return 'coupon';
	}
}
