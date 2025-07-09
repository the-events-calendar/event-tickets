<?php

namespace TEC\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce\Cart;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;

/**
 * Class Unmanaged_Cart
 *
 * Models a transitional, not managed, cart implementation; cart management functionality
 * is offloaded to PayPal.
 *
 * @since 5.1.9
 */
class Unmanaged_Cart extends Abstract_Cart {

	/**
	 * @var array|null The list of items, null if not retrieved from transient yet.
	 */
	protected $items = null;

	/**
	 * Saves the cart.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 *
	 * @since 5.1.9
	 */
	public function save() {
		$cart_hash = tribe( Cart::class )->get_cart_hash( true );

		if ( false === $cart_hash ) {
			return false;
		}

		$this->set_hash( $cart_hash );

		if ( ! $this->has_items() ) {
			$this->clear();
			return false;
		}

		set_transient(
			$this->get_transient_key( $cart_hash ),
			$this->items,
			$this->get_transient_expiration()
		);

		tribe( Cart::class )->set_cart_hash_cookie( $cart_hash );

		return true;
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
		if ( null !== $this->items ) {
			return $this->items;
		}

		if ( ! $this->exists() ) {
			return [];
		}

		$cart_hash = $this->get_hash();

		$items = get_transient( $this->get_transient_key( $cart_hash ) );

		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->items = $items;
		}

		return $this->items;
	}

	/**
	 * Clears the cart of its contents and persists its new state.
	 *
	 * @since 5.21.0 Added calculation caching reset.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 */
	public function clear() {
		$cart_hash = tribe( Cart::class )->get_cart_hash() ?? '';

		if ( false === $cart_hash ) {
			return;
		}

		$this->set_hash( null );
		delete_transient( $this->get_transient_key( $cart_hash ) );
		tribe( Cart::class )->set_cart_hash_cookie( null );

		// clear cart items data.
		$this->items      = [];
		$this->cart_total = null;
		$this->reset_calculations();
	}

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since 5.1.9
	 *
	 * @param array $criteria The criteria to check for.
	 *
	 * @return bool Whether the cart exists or not.
	 */
	public function exists( array $criteria = [] ) {
		$cart_hash = tribe( Cart::class )->get_cart_hash();

		if ( false === $cart_hash ) {
			return false;
		}

		return (bool) get_transient( $this->get_transient_key( $cart_hash ) );
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
		$items = $this->get_items();

		// When we don't have items, return false.
		if ( empty( $items ) ) {
			return false;
		}

		return count( $items );
	}

	/**
	 * Whether an item is in the cart or not.
	 *
	 * @since 5.1.9
	 *
	 * @param string $item_id The item ID.
	 *
	 * @return bool|int Either the quantity in the cart for the item or `false`.
	 */
	public function has_item( $item_id ) {
		$items = $this->get_items();

		return ! empty( $items[ $item_id ]['quantity'] ) ? (int) $items[ $item_id ]['quantity'] : false;
	}

	/**
	 * Adds a specified quantity of the item to the cart.
	 *
	 * @since 5.1.9
	 * @since 5.21.0 Added calculation caching reset.
	 *
	 * @param int|string $item_id    The item ID.
	 * @param int        $quantity   The quantity to add.
	 * @param array      $extra_data Extra data to save to the item.
	 */
	public function add_item( $item_id, $quantity, array $extra_data = [] ) {
		$current_quantity = $this->has_item( $item_id );

		$new_quantity = (int) $quantity;

		if ( 0 < $current_quantity ) {
			$new_quantity += (int) $current_quantity;
		}

		$new_quantity = max( $new_quantity, 0 );

		if ( 0 < $new_quantity ) {
			$item['ticket_id'] = $item_id;
			$item['quantity']  = $new_quantity;
			$item['extra']     = $extra_data;

			$this->items[ $item_id ] = $item;
		} else {
			$this->remove_item( $item_id );
		}

		$this->reset_calculations();
	}

	/**
	 * Removes an item from the cart.
	 *
	 * @since 5.1.9
	 * @since 5.21.0 Added calculation caching reset.
	 *
	 * @param int|string $item_id  The item ID.
	 * @param null|int   $quantity The quantity to remove.
	 */
	public function remove_item( $item_id, $quantity = null ) {
		if ( null !== $quantity ) {
			$this->add_item( $item_id, - abs( (int) $quantity ) );
			$this->reset_calculations();

			return;
		}

		if ( $this->has_item( $item_id ) ) {
			unset( $this->items[ $item_id ] );
		}

		$this->reset_calculations();
	}

	/**
	 * Process the items in the cart.
	 *
	 * Data passed in to process should override anything else that is already
	 * in the cart.
	 *
	 * @since 5.1.10
	 *
	 * @param array $data Data to be processed by the cart.
	 *
	 * @return array|bool An array of WP_Error objects if there are errors, otherwise `true`.
	 */
	public function process( array $data = [] ) {
		if ( empty( $data ) ) {
			return false;
		}

		// Reset the contents of the cart.
		$this->clear();

		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		// Prepare the error message array.
		$errors = [];

		// Natively handle adding tickets as items to the cart.
		foreach ( $data['tickets'] as $ticket ) {
			// Enforces that the min to add is 1.
			$quantity = max( 1, (int) $ticket['quantity'] );

			// Check if the ticket can be added to the cart.
			$can_add_to_cart = $tickets_handler->ticket_has_capacity( $ticket['ticket_id'], $quantity, $ticket['obj'] );

			// Skip and add to the errors if the ticket can't be added to the cart.
			if ( is_wp_error( $can_add_to_cart ) ) {
				$errors[] = $can_add_to_cart;

				continue;
			}

			// Add to / update quantity in cart.
			$this->add_item( $ticket['ticket_id'], $ticket['quantity'], $ticket['extra'] );
		}

		/**
		 * Fires after the ticket data has been processed.
		 *
		 * This allows for further processing of data within the $data array.
		 *
		 * @since 5.21.0
		 *
		 * @param Cart_Interface $cart The cart object.
		 * @param array          $data The data to be processed by the cart.
		 */
		do_action( 'tec_tickets_commerce_cart_process', $this, $data );

		// Saved added items to the cart.
		$this->save();

		if ( ! empty( $errors ) ) {
			return $errors;
		}

		return true;
	}

	/**
	 * Insert or update an item.
	 *
	 * @since 5.21.0
	 *
	 * @param string|int $item_id     The item ID.
	 * @param int        $quantity    The quantity of the item. If the item exists, this quantity will override
	 *                                the previous quantity. Passing 0 will remove the item from the cart entirely.
	 * @param array      $extra_data  Extra data to save to the item.
	 *
	 * @return void
	 */
	public function upsert_item( $item_id, int $quantity, array $extra_data = [] ) {
		$this->add_item( $item_id, $quantity, $extra_data );
	}
}
