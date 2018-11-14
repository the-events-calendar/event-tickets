<?php
/**
 * Attendee Registration core class
 *
 * @since TBD
 */
class Tribe__Tickets__Attendee_Registration__Main {
	/**
	 * The query var
	 *
	 * @since TBD
	 *
	 */
	public $key_query_var = 'attendee-registration';

	/**
	 * Default attendee registration slug
	 *
	 * @since TBD
	 *
	 */
	public $default_page_slug = 'attendee-registration';

	/**
	 * Retrieve the attendee registration slug
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_slug() {
		return Tribe__Settings_Manager::get_option( 'ticket-attendee-info-slug', $this->default_page_slug );
	}

	/**
	 * Returns whether or not the user is on the attendee registration page
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function is_on_page() {
		global $wp_query;

		return ! empty( $wp_query->query_vars[ $this->key_query_var ] );
	}

	/**
	 * Gets the URL for the attendee registration page
	 *
	 * @since TBD
	 * @return string
	 */
	public function get_url() {
		$slug = $this->get_slug();

		return home_url( "/{$slug}/" );
	}

	/**
	 * Gets the URL for the checkout url
	 *
	 * @since TBD
	 * @return string
	 */
	public function get_checkout_url() {
		/**
		 * Gets the attendee registration checkout URL
		 * @since TBD
		 */
		$checkout_url = apply_filters( 'tribe_tickets_attendee_registration_checkout_url', null );

		return $checkout_url;
	}

	/**
	 * See if the tickets in the cart have required fields
	 * Wrapper of Tribe__Tickets_Plus__Meta method
	 *
	 * @since TBD
	 *
	 * @param int $cart_items
	 * @return bool
	 */
	public function cart_has_required_meta( $cart_items ) {

		// Bail if ET+ is not active
		if (
			! class_exists( 'Tribe__Tickets_Plus__Meta' )
			|| ! class_exists( 'Tribe__Tickets_Plus__Meta__Storage' )
		) {
			return false;
		}

		$meta = tribe( 'tickets-plus.main' )->meta();

		return $meta->cart_has_required_meta( $cart_items );

	}
}