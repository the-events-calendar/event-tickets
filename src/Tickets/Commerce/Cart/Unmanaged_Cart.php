<?php

namespace TEC\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce;

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
	 * @var string The Cart hash for this cart.
	 */
	protected $cart_hash;

	/**
	 * @var array|null The list of items, null if not retrieved from transient yet.
	 */
	protected $items = null;

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
	 * Gets the Cart mode based.
	 *
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_mode() {
		return \TEC\Tickets\Commerce\Cart::REDIRECT_MODE;
	}

	/**
	 * Sets the cart hash.
	 *
	 * @since 5.1.9
	 * @since 5.2.0 Renamed to set_hash instead of set_id
	 *
	 * @param string $hash
	 */
	public function set_hash( $hash ) {
		/**
		 * Filters the cart setting of a hash used for the Cart.
		 *
		 * @since 5.2.0
		 *
		 * @param string         $cart_hash Cart hash value.
		 * @param Cart_Interface $cart      Which cart object we are using here.
		 */
		$this->cart_hash = apply_filters( 'tec_tickets_commerce_cart_set_hash', $hash, $this );
	}

	/**
	 * Gets the cart hash.
	 *
	 * @since 5.2.0
	 *
	 * @return string
	 */
	public function get_hash() {
		/**
		 * Filters the cart hash used for the Cart.
		 *
		 * @since 5.2.0
		 *
		 * @param string         $cart_hash Cart hash value.
		 * @param Cart_Interface $cart      Which cart object we are using here.
		 */
		return apply_filters( 'tec_tickets_commerce_cart_get_hash', $this->cart_hash, $this );
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
		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash( true );

		if ( false === $cart_hash ) {
			return false;
		}

		$this->set_hash( $cart_hash );

		if ( ! $this->has_items() ) {
			$this->clear();
			return false;
		}

		set_transient( Commerce\Cart::get_transient_name( $cart_hash ), $this->items, DAY_IN_SECONDS );
		tribe( Commerce\Cart::class )->set_cart_hash_cookie( $cart_hash );
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

		$items = get_transient( Commerce\Cart::get_transient_name( $cart_hash ) );

		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->items = $items;
		}

		return $this->items;
	}

	/**
	 * Clears the cart of its contents and persists its new state.
	 *
	 * This method should include any persistence, request and redirection required
	 * by the cart implementation.
	 */
	public function clear() {
		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash();

		if ( false === $cart_hash ) {
			return false;
		}

		$this->set_hash( null );
		delete_transient( Commerce\Cart::get_transient_name( $cart_hash ) );
		tribe( Commerce\Cart::class )->set_cart_hash_cookie( null );

		// clear cart items data.
		$this->items = [];
		$this->cart_total = null;
	}

	/**
	 * Whether a cart exists meeting the specified criteria.
	 *
	 * @since 5.1.9
	 *
	 * @param array $criteria The criteria to check for.
	 */
	public function exists( array $criteria = [] ) {
		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash();

		if ( false === $cart_hash ) {
			return false;
		}

		return (bool) get_transient( Commerce\Cart::get_transient_name( $cart_hash ) );
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

		return count( $items );
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
		$items = $this->get_items();

		return ! empty( $items[ $item_id ]['quantity'] ) ? (int) $items[ $item_id ]['quantity'] : false;
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
		if ( null !== $quantity ) {
			$this->add_item( $item_id, - abs( (int) $quantity ) );

			return;
		}

		if ( $this->has_item( $item_id ) ) {
			unset( $this->items[ $item_id ] );
		}
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
		if ( empty( $data ) ) {
			return false;
		}

		$this->clear();

		/** @var \Tribe__Tickets__REST__V1__Messages $messages */
		$messages = tribe( 'tickets.rest-v1.messages' );

		// Get the number of available tickets.
		/** @var \Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$errors = [];

		foreach ( $data['tickets'] as $ticket ) {
			$available = $tickets_handler->get_ticket_max_purchase( $ticket['ticket_id'] );

			// Bail if ticket does not have enough available capacity.
			if ( ( - 1 !== $available && $available < $ticket['quantity'] ) || ! $ticket['obj']->date_in_range() ) {
				$error_code = 'ticket-capacity-not-available';

				$errors[] = new \WP_Error( $error_code, sprintf( $messages->get_message( $error_code ), $ticket['obj']->name ), [
					'ticket'        => $ticket,
					'max_available' => $available,
				] );
				continue;
			}

			// Enforces that the min to add is 1.
			$ticket['quantity'] = max( 1, (int) $ticket['quantity'] );

			// Add to / update quantity in cart.
			$this->add_item( $ticket['ticket_id'], $ticket['quantity'], $ticket['extra'] );
		}

		// Saved added items to the cart.
		$this->save();

		if ( ! empty( $errors ) ) {
			return $errors;
		}

		return true;
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
		return $data;
	}
}
