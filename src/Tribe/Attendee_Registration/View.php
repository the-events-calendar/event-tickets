<?php

/**
 * Class Tribe__Tickets__Attendee_Registration__View
 */
class Tribe__Tickets__Attendee_Registration__View extends Tribe__Template {
	/**
	 * Tribe__Tickets__Attendee_Registration__View constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->set_template_origin( tribe( 'tickets.main' ) );
		$this->set_template_folder( 'src/views/registration' );
		$this->set_template_context_extract( true );
		$this->set_template_folder_lookup( true );
	}

	/**
	 * Display the Attendee Info page when the correct permalink is loaded.
	 *
	 * @since TBD
	 * @param string $content The original page|post content
	 * @return srting $template The resulting template content
	 */
	public function display_attendee_registration_page( $content = '' ) {
		global $wp_query;

		// Bail if we don't have the flag to be in the registration page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $content;
		}

		/**
		 * Filter to add/remove tickets from the global cart
		 *
		 * @since TDB
		 *
		 * @param array  The array containing the cart elements. Format arrat( 'ticket_id' => 'quantity' );
		 */
		$cart_tickets = apply_filters( 'tribe_tickets_tickets_in_cart', array() );
		$events       = array();

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			// Load the tickets in cart for each event, with their ID, quantity and provider.
			$ticket = tribe( 'tickets.handler' )->get_object_connections( $ticket_id );

			$ticket_data = array(
				'id'       => $ticket_id,
				'qty'      => $quantity,
				'provider' => $ticket->provider,
			);

			$events[ $ticket->event ][] = $ticket_data;
		}

		/**
		 * Check if the cart has a ticket with required meta fields
		 *
		 * @since TDB
		 *
		 * @param array  The array containing the cart elements. Format arrat( 'ticket_id' => 'quantity' );
		 */
		$cart_has_required_meta = (bool) apply_filters( 'tribe_tickets_attendee_registration_has_required_meta', $cart_tickets );

		// Get the checkout URL, it'll be added to the checkout button
		$checkout_url       = tribe( 'tickets.attendee_registration' )->get_checkout_url();

		/**
		 * Filter to check if there's any required meta that wasn't filled in
		 *
		 * @since TDB
		 *
		 * @param bool
		 */
		$is_meta_up_to_date = (int) apply_filters( 'tribe_tickets_attendee_registration_is_meta_up_to_date', true );

		/**
		 *  Set all the template variables
		 */
		$args = array(
			'events'                 => $events,
			'checkout_url'           => $checkout_url,
			'is_meta_up_to_date'     => $is_meta_up_to_date,
			'cart_has_required_meta' => $cart_has_required_meta,
		);

		// enqueue styles and scripts for this page
		tribe_asset_enqueue( 'event-tickets-registration-page-styles' );
		tribe_asset_enqueue( 'event-tickets-registration-page-scripts' );

		wp_enqueue_style( 'dashicons' );

		/** @var Tribe__Tickets__Attendee_Registration__View $view */
		$view     = tribe( 'tickets.attendee_registration.view' );
		$template = $view->template( 'content', $args, false );

		return $template;
	}

	/**
	 * Get the provider Cart URL if WooCommerce is the provider.
	 * Checks the provider by post id (event)
	 *
	 * @since TBD
	 *
	 * @param int $post_id
	 * @return bool|string
	 */
	public function get_cart_url( $post_id ) {

		$post_provider = get_post_meta( $post_id, tribe( 'tickets.handler' )->key_provider_field, true );

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' !== $post_provider ) {
			return false;
		}

		$provider = new $post_provider;

		return $provider->get_cart_url();
	}
}
