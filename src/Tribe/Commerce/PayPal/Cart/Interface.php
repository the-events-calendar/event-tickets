<?php

/**
 * Interface Tribe__Tickets__Commerce__PayPal__Cart__Interface
 *
 * @since TBD
 */
interface Tribe__Tickets__Commerce__PayPal__Cart__Interface {
	/**
	 * Sets the cart id.
	 *
	 * @since TBD
	 *
	 * @param string $id
	 */
	public function set_id( $id );

	/**
	 * Adds a specified quantity of the item to the cart.
	 *
	 * @since TBD
	 *
	 * @param int $item_id
	 * @param int $quantity
	 */
	public function add_item( $item_id, $quantity );

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
	public function exists( array $criteria = array() );

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
	 * @param string $item_id
	 *
	 * @return bool|int Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id );

	/**
	 * Removes an item from the cart.
	 *
	 * @since TBD
	 *
	 * @param string $item_id
	 *
	 * @param int $quantity The quantity to remove.
	 */
	public function remove_item( $item_id, $quantity );
}