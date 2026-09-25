<?php
/**
 * Converts fee order items to Order Items table rows and back.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

/**
 * Class Fee_Line_Item.
 *
 * A fee's `ticket_id` is the ticket it applies to; the line does not take that ticket's details.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
class Fee_Line_Item extends Abstract_Line_Item_Type {
	/**
	 * Item keys stored in a column, and how their value is stored.
	 *
	 * @since TBD
	 *
	 * @var array<string,string>
	 */
	protected const FIELDS = parent::FIELDS + [
		'fee_id'       => 'int',
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
		'fee_id' => 'modifier_id',
	];

	/**
	 * Returns the item type this class converts.
	 *
	 * @since TBD
	 *
	 * @return string The item type.
	 */
	public function get_type(): string {
		return 'fee';
	}
}
