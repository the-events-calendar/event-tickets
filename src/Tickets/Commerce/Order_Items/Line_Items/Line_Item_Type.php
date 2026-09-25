<?php
/**
 * Converts one type of order item to an Order Items table row and back.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */

namespace TEC\Tickets\Commerce\Order_Items\Line_Items;

/**
 * Interface Line_Item_Type.
 *
 * The item read back from a row is identical (`===`) to the item written, key order and value types included.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Line_Items
 */
interface Line_Item_Type {
	/**
	 * Returns the item `type` this class converts.
	 *
	 * @since TBD
	 *
	 * @return string The item type, such as `ticket`.
	 */
	public function get_type(): string;

	/**
	 * Converts an order item to a table row.
	 *
	 * The row keys are the table's column names; `id` and `created_at` are left to the table.
	 *
	 * @since TBD
	 *
	 * @param int|string $key      The item's key in the order's item list.
	 * @param array      $item     The order item.
	 * @param int        $order_id The order ID.
	 * @param string     $currency The order's currency code.
	 *
	 * @return array<string,int|string|null> The row.
	 */
	public function to_row( $key, array $item, int $order_id, string $currency ): array;

	/**
	 * Converts a table row back to the order item it was written from.
	 *
	 * The key is returned as stored; assigning it as an array key turns '1' back into 1 and leaves '01' a string,
	 * which is the type it had.
	 *
	 * @since TBD
	 *
	 * @param array<string,int|string|array|null> $row The row, as built by to_row() or read from the database.
	 *
	 * @return array{0: string, 1: array} The item's key in the order's item list, and the item.
	 */
	public function from_row( array $row ): array;
}
