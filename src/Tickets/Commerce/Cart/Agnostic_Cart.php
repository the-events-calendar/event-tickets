<?php
/**
 *
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Traits\Cart as Cart_Trait;

/**
 * Class Agnostic_Cart
 *
 * @since TBD
 */
class Agnostic_Cart extends Abstract_Cart {

	use Cart_Trait;

	/**
	 * @var array The list of items.
	 */
	protected array $items = [];

	/**
	 * Gets the Cart mode based.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_mode() {
		// TODO: Implement get_mode() method.
	}

	/**
	 * Gets the cart items from the cart.
	 *
	 * This method should include any persistence by the cart implementation.
	 *
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function get_items() {
		// TODO: Implement get_items() method.
	}

	/**
	 * Saves the cart.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 *
	 * @since 5.1.9
	 */
	public function save() {
		// TODO: Implement save() method.
	}

	/**
	 * Clears the cart of its contents and persists its new state.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 */
	public function clear() {
		// TODO: Implement clear() method.
	}

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since 5.1.9
	 *
	 * @param array $criteria
	 */
	public function exists( array $criteria = [] ) {
		$hash = $this->get_cart_object()->get_cart_hash();

	}

	/**
	 * Whether the cart contains items or not.
	 *
	 * @since 5.1.9
	 *
	 * @return bool|int The number of products in the cart (regardless of the products quantity) or `false`
	 *
	 */
	public function has_items() {
		$count = count( $this->get_items() );

		return $count > 0 ? $count : false;
	}

	/**
	 * Whether an item is in the cart or not.
	 *
	 * @since 5.1.9
	 *
	 * @param string $item_id
	 *
	 * @return bool|int Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id ) {
		if ( empty( $this->items ) ) {
			return false;
		}

		if ( ! array_key_exists( $item_id, $this->items ) ) {
			return false;
		}

		return $this->items[ $item_id ][ 'quantity' ] ?? false;
	}

	/**
	 * Adds a specified quantity of the item to the cart.
	 *
	 * @since 5.1.9
	 *
	 * @param int|string $item_id    The item ID.
	 * @param int        $quantity   The quantity to remove.
	 * @param array      $extra_data Extra data to save to the item.
	 */
	public function add_item( $item_id, $quantity, array $extra_data = [] ) {
		// TODO: Implement add_item() method.
	}

	/**
	 * Determines if this instance of the cart has a public page.
	 *
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function has_public_page() {
		return false;
	}

	/**
	 * Removes an item from the cart.
	 *
	 * @since 5.1.9
	 *
	 * @param int|string $item_id  The item ID.
	 * @param null|int   $quantity The quantity to remove.
	 */
	public function remove_item( $item_id, $quantity = null ) {
		// TODO: Implement remove_item() method.
	}

	/**
	 * Process the items in the cart.
	 *
	 * @since 5.1.10
	 *
	 * @param array $data to be processed by the cart.
	 *
	 * @return array
	 */
	public function process( array $data = [] ) {
		// TODO: Implement process() method.
	}

	/**
	 * Prepare the data for cart processing.
	 *
	 * @since 5.1.10
	 *
	 * @param array $data To be processed by the cart.
	 *
	 * @return array
	 */
	public function prepare_data( array $data = [] ) {
		// TODO: Implement prepare_data() method.
	}

	protected function get_cart_object(): Cart {
		return tribe( Cart::class );
	}
}
