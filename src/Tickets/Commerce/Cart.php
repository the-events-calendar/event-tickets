<?php

namespace TEC\Tickets\Commerce;

use TEC\Tickets\Commerce;


/**
 * Class Cart
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Cart {

	/**
	 * Which cookie we will store the invoice number.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $invoice_cookie_name = 'tec-tickets-commerce-invoice';

	/**
	 * Returns the name of the transient used by the cart.
	 *
	 * @since TBD
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function get_transient_name( $id ) {
		return Commerce::ABBR . '-cart-' . md5( $id );
	}

	/**
	 * Returns the name of the transient used by the cart for invoice numbers
	 *
	 * @since TBD
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public static function get_invoice_transient_name( $id ) {
		return Commerce::ABBR . '-invoice-' . md5( $id );
	}

	/**
	 * Determine the Current cart Transient Key based on invoice number.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_current_cart_transient() {
		$invoice_number = $this->get_invoice_number();
		return static::get_transient_name( $invoice_number );
	}

	/**
	 * Determine the Current cart URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_url() {
		$url = home_url( '/' );

		// If the cart doesn't have a public URL we go directly to the Checkout page.
		if ( ! $this->get_repository()->has_public_page() ) {
			$url = tribe( Checkout::class )->get_url();
		}

		/**
		 * Allows modifications to the cart url for Tickets Commerce.
		 *
		 * @since TBD
		 *
		 * @param string $url URL for the cart.
		 */
		return (string) apply_filters( 'tec_tickets_commerce_cart_url', $url );
	}

	/**
	 * Reads the invoice number from the invoice cookie.
	 *
	 * @since TBD
	 *
	 * @return string|bool The invoice number or `false` if not found.
	 */
	public function get_invoice_number( $generate = false ) {
		$invoice_length = 12;

		$invoice = null;

		if (
			! empty( $_COOKIE[ static::$invoice_cookie_name ] )
			&& strlen( $_COOKIE[ static::$invoice_cookie_name ] ) === $invoice_length
		) {
			$invoice = $_COOKIE[ static::$invoice_cookie_name ];

			$invoice_transient = get_transient( static::get_invoice_transient_name( $invoice ) );

			if ( empty( $invoice_transient ) ) {
				$invoice = false;
			}
		}

		if ( empty( $invoice ) && $generate ) {
			$invoice = wp_generate_password( $invoice_length, false );
		}

		/**
		 * Filters the invoice number used for Unmanaged Cart.
		 *
		 * @since TBD
		 *
		 * @param string $invoice Invoice number.
		 */
		$invoice = apply_filters( 'tec_tickets_commerce_cart_invoice_number', $invoice );

		return $invoice;
	}

	/**
	 * Gets the current instance of cart handling that we are using.
	 *
	 * @since TBD
	 *
	 * @return Commerce\Cart\Cart_Interface
	 */
	public function get_repository() {
		$default_cart = tribe( Cart\Unmanaged_Cart::class );

		/**
		 * Filters the cart repository, by default we use Unmanaged Cart.
		 *
		 * @since TBD
		 *
		 * @param Cart\Cart_Interface $cart Instance of the cart repository managing the cart.
		 */
		return apply_filters( 'tec_tickets_commerce_cart_repository', $default_cart );
	}

	/**
	 * Get the tickets currently in the cart for a given provider.
	 *
	 * @since TBD
	 *
	 * @param string $provider Provider of tickets to get (if set).
	 *
	 * @return array List of tickets.
	 */
	public function get_tickets_in_cart( $provider = null ) {
		$cart = $this->get_repository();

		return $cart->get_items();
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id  Ticket ID.
	 * @param int   $quantity   Ticket quantity to add.
	 * @param array $extra_data Extra data to send to the cart item.
	 */
	public function add_ticket( $ticket_id, $quantity = 1, array $extra_data = [] ) {
		$cart = $this->get_repository();

		// Enforces that the min to add is 1.
		$quantity = max( 1, (int) $quantity );

		$optout = isset( $extra_data[ Attendee::$optout_meta_key ] ) ? $extra_data[ Attendee::$optout_meta_key ] : false;
		$optout = filter_var( $optout, FILTER_VALIDATE_BOOLEAN );
		$optout = $optout ? 'yes' : 'no';

		$extra_item_data = [
			Attendee::$optout_meta_key => $optout,
		];

		// Add to / update quantity in cart.
		$cart->add_item( $ticket_id, $quantity, $extra_item_data );
	}

	/**
	 * Handles the process of adding a ticket product to the cart.
	 *
	 * If the cart contains a line item for the product, this will replace the previous quantity.
	 * If the quantity is zero and the cart contains a line item for the product, this will remove it.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id Ticket ID.
	 * @param int $quantity  Ticket quantity to remove.
	 */
	public function remove_ticket( $ticket_id, $quantity = 1 ) {
		$cart = $this->get_repository();

		// Enforces that the min to remove is 1.
		$quantity = max( 1, (int) $quantity );

		$cart->remove_item( $ticket_id, $quantity );
	}

	/**
	 * If product cache parameter is found, delete saved products from temporary cart.
	 *
	 * @filter wp_loaded 0
	 *
	 * @since  TBD
	 */
	public function maybe_delete_expired_products() {
		$delete = tribe_get_request_var( 'clear_product_cache', null );

		if ( empty( $delete ) ) {
			return;
		}

		$transient_key = $this->get_current_cart_transient();

		// Bail if we have no data key.
		if ( false === $transient_key ) {
			return;
		}

		$transient = get_transient( $transient_key );

		// Bail if we have no data to delete.
		if ( empty( $transient ) ) {
			return;
		}

		// Bail if ET+ is not in place.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' ) ) {
			return;
		}
		$storage = new \Tribe__Tickets_Plus__Meta__Storage();

		foreach ( $transient as $ticket_id => $data ) {
			$storage->delete_cookie( $ticket_id );
		}
	}
}