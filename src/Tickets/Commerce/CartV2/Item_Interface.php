<?php
/**
 * Interface for Cart Item.
 *
 * @package TEC\Tickets\Commerce\CartV2
 */

namespace TEC\Tickets\Commerce\CartV2;

/**
 * Interface Item_Interface
 *
 * Represents the contract for an item that can be added to a cart.
 */
interface Item_Interface {
	/**
	 * Retrieves the ID of the item.
	 *
	 * @param bool $prefixed Whether to return a prefixed ID.
	 *
	 * @return string|int The item's ID.
	 */
	public function get_id( bool $prefixed );

	/**
	 * Retrieves the quantity of the item.
	 *
	 * @return int The quantity of the item.
	 */
	public function get_quantity(): int;

	/**
	 * Retrieves the value of the item in cents.
	 *
	 * @return int The value of the item in cents.
	 */
	public function get_value(): int;

	/**
	 * Retrieves the subtype of the item (e.g., 'flat', 'percent').
	 *
	 * @return string The subtype of the item.
	 */
	public function get_sub_type(): string;

	/**
	 * Calculates the amount for the item, potentially based on a given subtotal.
	 *
	 * @param int|null $subtotal Optional. The subtotal value to base the calculation on.
	 *
	 * @return int The calculated amount in cents.
	 */
	public function get_amount( ?int $subtotal = null ): int;

	/**
	 * Checks if the item should be counted in the subtotal.
	 *
	 * @return bool True if the item is included in the subtotal calculation, false otherwise.
	 */
	public function is_counted_in_subtotal(): bool;

	/**
	 * Performs an action when the item is added to the cart.
	 *
	 * @param Cart $cart The cart to which the item is being added.
	 *
	 * @return void
	 */
	public function added_to_cart( Cart $cart ): void;
}
