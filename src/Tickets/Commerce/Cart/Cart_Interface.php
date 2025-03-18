<?php

namespace TEC\Tickets\Commerce\Cart;

use InvalidArgumentException;

/**
 * Interface Cart_Interface
 *
 * @since 5.1.9
 * @since 5.21.0 Updated the interface: remove add_item(), add upsert_item(), add get_item_quantity().
 *
 * @package TEC\Tickets\Commerce\Cart
 */
interface Cart_Interface {

	/**
	 * Sets the cart hash.
	 *
	 * @since 5.1.9
	 * @since 5.2.0 Renamed to set_hash instead of set_id
	 *
	 * @param string $hash The hash to set.
	 */
	public function set_hash( $hash );

	/**
	 * Gets the cart hash.
	 *
	 * @since 5.2.0
	 *
	 * @return string The hash.
	 */
	public function get_hash();

	/**
	 * Gets the Cart mode based.
	 *
	 * @since 5.1.9
	 *
	 * @return string The mode.
	 */
	public function get_mode();

	/**
	 * Gets the cart items from the cart.
	 *
	 * This method should include any persistence by the cart implementation.
	 *
	 * @since 5.1.9
	 *
	 * @return array The items in the cart.
	 */
	public function get_items();

	/**
	 * Saves the cart.
	 *
	 * This method should include any persistence, request, and redirection required
	 * by the cart implementation.
	 *
	 * @since 5.1.9
	 */
	public function save();

	/**
	 * Clears the cart of its contents and persists its new state.
	 *
	 * This method should include any persistence, request, and redirection required
	 * by the cart implementation.
	 *
	 * @since 5.1.9
	 */
	public function clear();

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since 5.1.9
	 *
	 * @param array $criteria Additional criteria to use when checking if the cart exists.
	 *
	 * @return bool Whether the cart exists or not.
	 */
	public function exists( array $criteria = [] );

	/**
	 * Whether the cart contains items or not.
	 *
	 * @since 5.1.9
	 *
	 * @return bool|int The number of products in the cart (regardless of the products quantity) or `false`.
	 */
	public function has_items();

	/**
	 * Whether an item is in the cart or not.
	 *
	 * @since 5.1.9
	 *
	 * @param string $item_id The item ID.
	 *
	 * @return bool|int Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id );

	/**
	 * Determines if this instance of the cart has a public page.
	 *
	 * @since 5.1.9
	 *
	 * @return bool Whether the cart has a public page or not.
	 */
	public function has_public_page();

	/**
	 * Removes an item from the cart.
	 *
	 * @since 5.1.9
	 * @since 5.21.0 Removed the $quantity parameter.
	 *
	 * @param int|string $item_id The item ID.
	 */
	public function remove_item( $item_id );

	/**
	 * Process the items in the cart.
	 *
	 * Data passed in to process should override anything else that is already
	 * in the cart.
	 *
	 * @since 5.1.10
	 *
	 * @param array $data to be processed by the cart.
	 *
	 * @return array The processed data.
	 */
	public function process( array $data = [] );

	/**
	 * Prepare the data for cart processing.
	 *
	 * This method should be used to do any pre-processing of the data before
	 * it is passed to the process() method. If no pre-processing is needed,
	 * this method should return the data as-is.
	 *
	 * @since 5.1.10
	 *
	 * @param array $data To be processed by the cart.
	 *
	 * @return array The prepared data.
	 */
	public function prepare_data( array $data = [] );

	/**
	 * Insert or update an item.
	 *
	 * Use this method to add a new item, or to update the quantity and extra data of an existing item.
	 *
	 * @since 5.21.0
	 *
	 * @param string|int $item_id    The item ID.
	 * @param int        $quantity   The quantity of the item. If the item exists, this quantity will override
	 *                               the previous quantity. Passing 0 will remove the item from the cart entirely.
	 * @param array      $extra_data Extra data to save to the item.
	 *
	 * @return void
	 */
	public function upsert_item( $item_id, int $quantity, array $extra_data = [] );

	/**
	 * Get the quantity of an item in the cart.
	 *
	 * @since 5.21.0
	 *
	 * @param int|string $item_id The item ID.
	 *
	 * @return int The quantity of the item in the cart.
	 *
	 * @throws InvalidArgumentException If the item is not in the cart.
	 */
	public function get_item_quantity( $item_id ): int;
}
