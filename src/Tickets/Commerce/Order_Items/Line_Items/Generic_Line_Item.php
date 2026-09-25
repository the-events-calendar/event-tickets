<?php
/**
 * Converts order items of an unregistered type, or with no type, to Order Items table rows and back.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

/**
 * Class Generic_Line_Item.
 *
 * Such an item is stored like a ticket line, the shape every order item is built in, so it keeps the details
 * of the ticket it names; whatever else it holds round-trips through `extra`.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
class Generic_Line_Item extends Ticket_Line_Item {
	/**
	 * Returns the item type this class converts: none, as it converts any unregistered type.
	 *
	 * @since TBD
	 *
	 * @return string The item type.
	 */
	public function get_type(): string {
		return '';
	}
}
