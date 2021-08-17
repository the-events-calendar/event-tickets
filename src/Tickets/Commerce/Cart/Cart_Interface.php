<?php

namespace TEC\Tickets\Commerce\Cart;

/**
 * Interface Cart_Interface
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Cart
 */
interface Cart_Interface {

	/**
	 * Sets the cart id.
	 *
	 * @since TBD
	 *
	 * @param string $id
	 */
	public function set_id( $id );

	/**
	 * Gets the Cart mode based.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_mode();

	/**
	 * Gets the cart items from the cart.
	 *
	 * This method should include any persistence by the cart implementation.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_items();

	/**
	 * Saves the cart.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 *
	 * @since TBD
	 */
	public function save();

	/**
	 * Clears the cart of its contents and persists its new state.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 */
	public function clear();

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since TBD
	 *
	 * @param array $criteria
	 */
	public function exists( array $criteria = [] );

	/**
	 * Whether the cart contains items or not.
	 *
	 * @since TBD
	 *
	 * @return bool|int The number of products in the cart (regardless of the products quantity) or `false`
	 *
	 */
	public function has_items();

	/**
	 * Whether an item is in the cart or not.
	 *
	 * @since TBD
	 *
	 * @param string $item_id
	 *
	 * @return bool|int Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id );

	/**
	 * Adds a specified quantity of the item to the cart.
	 *
	 * @since TBD
	 *
	 * @param int|string $item_id    The item ID.
	 * @param int        $quantity   The quantity to remove.
	 * @param array      $extra_data Extra data to save to the item.
	 */
	public function add_item( $item_id, $quantity, array $extra_data = [] );

	/**
	 * Determines if this instance of the cart has a public page.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function has_public_page();

	/**
	 * Removes an item from the cart.
	 *
	 * @since TBD
	 *
	 * @param int|string $item_id  The item ID.
	 * @param null|int   $quantity The quantity to remove.
	 */
	public function remove_item( $item_id, $quantity = null );
}
