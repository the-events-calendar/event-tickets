<?php

namespace TEC\Tickets\Commerce\Cart;

use TEC\Tickets\Commerce;

/**
 * Class Unmanaged_Cart
 *
 * Models a transitional, not managed, cart implementation; cart management functionality
 * is offloaded to PayPal.
 *
 * @since TBD
 */
class Unmanaged_Cart implements Cart_Interface {

	/**
	 * @var string The Cart hash for this cart.
	 */
	protected $cart_hash;

	/**
	 * @var array|null The list of items, null if not retrieved from transient yet.
	 */
	protected $items = null;

	/**
	 * {@inheritDoc}
	 */
	public function has_public_page() {
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_mode() {
		return \TEC\Tickets\Commerce\Cart::REDIRECT_MODE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function set_id( $id ) {
		$this->cart_hash = $id;
	}

	/**
	 * {@inheritdoc}
	 */
	public function save() {
		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash( true );

		if ( false === $cart_hash ) {
			return false;
		}

		if ( ! $this->has_items() ) {
			$this->clear();

			return;
		}

		set_transient( Commerce\Cart::get_transient_name( $cart_hash ), $this->items, DAY_IN_SECONDS );
		tribe( Commerce\Cart::class )->set_cart_hash_cookie( $cart_hash );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_items() {
		if ( null !== $this->items ) {
			return $this->items;
		}

		if ( ! $this->exists() ) {
			return false;
		}

		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash();

		$items = get_transient( Commerce\Cart::get_transient_name( $cart_hash ) );

		if ( is_array( $items ) && ! empty( $items ) ) {
			$this->items = $items;
		}

		return $this->items;
	}

	/**
	 * {@inheritdoc}
	 */
	public function clear() {
		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash();

		if ( false === $cart_hash ) {
			return false;
		}

		delete_transient( Commerce\Cart::get_transient_name( $cart_hash ) );
		tribe( Commerce\Cart::class )->set_cart_hash_cookie( $cart_hash );
	}

	/**
	 * {@inheritdoc}
	 */
	public function exists( array $criteria = [] ) {
		$cart_hash = tribe( Commerce\Cart::class )->get_cart_hash();

		if ( false === $cart_hash ) {
			return false;
		}

		return (bool) get_transient( Commerce\Cart::get_transient_name( $cart_hash ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_items() {
		$items = $this->get_items();

		return count( $items );
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_item( $item_id ) {
		$items = $this->get_items();

		return ! empty( $items[ $item_id ] ) ? (int) $items[ $item_id ]['quantity'] : false;
	}

	/**
	 * {@inheritdoc}
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
			$item['quantity'] = $new_quantity;

			$item['extra'] = $extra_data;

			$this->items[ $item_id ] = $item;
		} else {
			$this->remove_item( $item_id );
		}
	}

	/**
	 * {@inheritdoc}
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
}
