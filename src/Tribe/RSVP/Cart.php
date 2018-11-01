<?php

class Tribe__Tickets__RSVP__Cart {

	/**
	 * Cart contents
	 *
	 * @var array
	 * @since TBD
	 */
	public $contents = array();

	/**
	 * Cart ID
	 *
	 * @var int
	 * @since TBD
	 */
	public $cart_key = 'tribe_tickets_rsvp_cart';

	/**
	 * Register the RSVP Cart
	 *
	 * @since TBD
	 */
	public function hook() {
		// Initialize the cart
		$this->setup();
	}

	/**
	 * Setup the RSVP Cart
	 *
	 * @since TBD
	 */
	public function setup() {

		// Maybe setup multisite
		$this->maybe_setup_multisite();

		// If we don't have info, initialize from scratch
		if ( ! isset( $_COOKIE[ $this->cart_key ] ) ) {
			setcookie( $this->cart_key, serialize( array() ), time() + 3600 * 24 * 7, COOKIEPATH, COOKIE_DOMAIN );
			return true;
		}

		// else, load the cart contents
		$this->contents = unserialize( $_COOKIE[ $this->cart_key ] );

	}

	/**
	 * If we're on WP multisite, and using subdirectories,
	 * We'll need to add an ID to the cart_key so they have one per site
	 *
	 * @since TBD
	 */
	public function maybe_setup_multisite() {
		if ( is_multisite () ) {
			$this->cart_key .= '_' . get_current_blog_id();
		}
	}

	/**
	 * Checks if the cart is empty
	 *
	 * @since TBD
	 * @return boolean
	 */
	public function is_empty() {
		return 0 === count( $this->contents );
	}

	/**
	 * Checks if the ticket is RSVP
	 *
	 * @since TBD
	 * @return boolean
	 */
	public function is_ticket_rsvp( $ticket_id = 0 ) {
		// Check that the ticket ID exists and it is an RSVP
		$ticket = get_post( $ticket_id );

		return $ticket->post_type === tribe( 'tickets.rsvp' )->ticket_object;
	}

	/**
	 * Add to cart
	 *
	 *
	 * @since TBD
	 * @return array $cart Updated cart object
	 */
	public function add( $ticket_id, $options = array() ) {

		// Bail if they're tring to add something that isn't an RSVP ticket
		if ( ! $this->is_ticket_rsvp( $ticket_id ) ) {
			return;
		}

		// Bail if the quantity is not set
		if (
			! isset( $options['quantity'] )
			|| 1 > $options['quantity']
		) {
			return;
		}

		// Use a temprary var for the checks/additions
		$cart = $this->contents;

		// @todo: Check that we can add the quantity that they are asking to

		// Add the quantity to the cart
		if ( ! isset( $this->contents[ $ticket_id ] ) ) {
			$cart[ $ticket_id ]['quantity'] = (int) $options['quantity'];
		} else {
			$cart[ $ticket_id ]['quantity'] = $cart[ $ticket_id ] + $options['quantity'];
		}

		// We need to store these to submit them later
		$cart[ $ticket_id ]['email']        = $options['email'];
		$cart[ $ticket_id ]['full_name']    = $options['full_name'];
		$cart[ $ticket_id ]['optout']       = $options['optout'];
		$cart[ $ticket_id ]['order_status'] = $options['order_status'];

		$this->contents = $cart;

		$this->update();

		return $this->contents;

	}

	/**
	 * Remove from cart
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id
 	 * @return array Updated cart contents
	 */
	public function remove( $ticket_id, $quantity = 0 ) {

		// Bail if they're tring to add something that isn't an RSVP ticket
		if ( ! $this->is_ticket_rsvp( $ticket_id ) ) {
			return;
		}

		// Bail if the quantity is not set
		if ( 1 > $quantity ) {
			return;
		}

		// Bail if the ticket id isn't in the cart
		if ( ! $this->has_item( $ticket_id ) ) {
			return;
		}

		// Use a temporary variable
		$cart = $this->contents;

		// If the quantity they're trying to remove is higher
		// than what's in the cart, we remove it completely
		if ( $cart[ $ticket_id ]['quantity'] < $quantity ) {
			unset( $cart[ $ticket_id ] );
		}

		// Decrease the quantity
		$cart[ $ticket_id ]['quantity'] = (int) ( $cart[ $ticket_id ]['quantity'] - $quantity );

		$this->contents = $cart;

		$this->update();

		return $this->contents;
	}

	/**
	 * Empty the cart
	 *
	 * @since TBD
	 * @return void
	 */
	public function empty() {
		$this->contents = array();
		// remove cookie
		unset( $_COOKIE[ $this->cart_key ] );
		// empty value and expiration one hour before
		setcookie( $this->cart_key, serialize( $this->contents ), time() - 3600, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Update the the cart contents
	 *
	 * @since TBD
	 * @return void
	 */
	public function update() {
		setcookie( $this->cart_key, serialize( $this->contents ), time() + 3600 * 24 * 7, COOKIEPATH, COOKIE_DOMAIN );
	}

	/**
	 * Checks to see if an item is in the cart.
	 *
	 * @since TBD
	 *
	 * @param int   $ticket_id Download ID of the item to check.
 	 * @param array $options
	 * @return bool
	 */
	public function has_item( $ticket_id = 0 ) {
		return (bool) $this->get_item_quantity( $ticket_id );
	}

	/**
	 * Get the quantity of an item in the cart.
	 *
	 * @since TBD
	 *
	 * @param int   $download_id Download ID of the item
 	 * @param array $options
	 * @return int Numerical index of the position of the item in the cart
	 */
	public function get_item_quantity( $ticket_id = 0 ) {
		return isset( $this->contents[ $ticket_id ] ) ? $this->contents[ $ticket_id ]['quantity'] : 0;
	}

	/**
	 * Return the cart contents
	 *
	 * @since TBD
	 *
	 * @return array $contents Updated cart object.
	 */
	public function get_cart_contents() {
		return $this->contents;
	}
}