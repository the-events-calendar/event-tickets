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
	 */
	public function display_attendee_registration_page( $content = '' ) {
		global $wp_query;

		// Bail if we don't have the flag to be in the registration page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $content;
		}

		$cart_tickets = apply_filters( 'tribe_tickets_tickets_in_cart', array() );
		$events       = array();

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			// Only include those who have meta
			$has_meta = get_post_meta( $ticket_id, '_tribe_tickets_meta_enabled', true );

			if ( empty( $has_meta ) || ! tribe_is_truthy( $has_meta ) ) {
				continue;
			}

			// Load the tickets in cart for each event, with their ID, quantity and provider.
			$ticket = tribe( 'tickets.handler' )->get_object_connections( $ticket_id );
			$events[ $ticket->event ][] = array( 'id' => $ticket_id, 'qty' => $quantity, 'provider' => $ticket->provider );

		}

		// Get required variables for the template
		$checkout_url       = tribe( 'tickets.attendee_registration' )->get_checkout_url();
		$is_meta_up_to_date = (int) apply_filters( 'tribe_tickets_attendee_registration_is_meta_up_to_date', true );

		// Set all the template variables
		$args = array(
			'events'             => $events,
			'checkout_url'       => $checkout_url,
			'is_meta_up_to_date' => $is_meta_up_to_date,
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
}
