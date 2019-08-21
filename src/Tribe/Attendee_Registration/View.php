<?php

/**
 * Class Tribe__Tickets__Attendee_Registration__View
 */
class Tribe__Tickets__Attendee_Registration__View extends Tribe__Template {
	/**
	 * Tribe__Tickets__Attendee_Registration__View constructor.
	 *
	 * @since 4.9
	 */
	public function __construct() {
		$this->set_template_origin( tribe( 'tickets.main' ) );
		$this->set_template_folder( 'src/views' );
		$this->set_template_context_extract( true );
		$this->set_template_folder_lookup( true );
	}

	/**
	 * Display the Attendee Info page when the correct permalink is loaded.
	 *
	 * @since 4.9
	 * @param string $content The original page|post content
	 * @param string $context The context of the rendering
	 *
	 * @return string The resulting template content
	 */
	public function display_attendee_registration_page( $content = '', $context = 'default' ) {
		// Bail if we don't have the flag to be in the registration page (or we're not using a shortcode to display it)
		if ( 'shortcode' !== $context && ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $content;
		}

		$q_provider = tribe_get_request_var( 'provider', false );

		/**
		 * Filter to add/remove tickets from the global cart
		 *
		 * @since TDB
		 *
		 * @param array  $cart_tickets The array containing the cart elements. Format array( 'ticket_id' => 'quantity' );
		 * @param string $q_provider   Current ticket provider.
		 */
		$cart_tickets = apply_filters( 'tribe_tickets_tickets_in_cart', array(), $q_provider );
		$events       = array();
		$providers    = array();

		foreach ( $cart_tickets as $ticket_id => $quantity ) {
			// Load the tickets in cart for each event, with their ID, quantity and provider.
			$ticket = tribe( 'tickets.handler' )->get_object_connections( $ticket_id );

			// If we've got a provider and it doesn't match, skip the ticket
			if ( $q_provider && $q_provider !== $ticket->provider->attendee_object ) {
				continue;
			}

			$ticket_data = array(
				'id'       => $ticket_id,
				'qty'      => $quantity,
				'provider' => $ticket->provider,
			);

			/**
			 * Flag for event form to flag TPP. This is used for the AJAX
			 * feature for save attendee information. If the provider is
			 * TPP, then AJAX saving is disabled.
			 *
			 * @todo: This is temporary until we can figure out what to do
			 *        with the Attendee Registration page handling multiple
			 *        payment providers.
			 */
			$provider = '';
			switch ( $ticket->provider->class_name ) {
				case 'Tribe__Tickets__Commerce__PayPal__Main':
					$provider = 'tpp';
					break;
				case 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main':
					$provider = 'woo';
					break;
				case 'Tribe__Tickets_Plus__Commerce__EDD__Main':
					$provider = 'edd';
					break;
				default:
					break;
			}
			$providers[ $ticket->event ] = $provider;
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
		$checkout_url = tribe( 'tickets.attendee_registration' )->get_checkout_url();

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
			'providers'              => $providers,
			'context'                => $context,
			'original_content'       => $content,
		);

		// enqueue styles and scripts for this page
		tribe_asset_enqueue( 'event-tickets-registration-page-styles' );
		tribe_asset_enqueue( 'event-tickets-registration-page-scripts' );

		wp_enqueue_style( 'dashicons' );

		$this->add_template_globals( $args );

		return $this->template( 'registration/content', $args, false );
	}

	/**
	 * Get the provider Cart URL.
	 *
	 * @since 4.9
	 *
	 * @param int $provider
	 * @return bool|string
	 */
	public function get_cart_url( $provider ) {
		if ( empty( $provider ) ) {
			return false;
		}

		$post_provider = $this->get_cart_provider( $provider );

		if ( empty( $post_provider ) ) {
			return false;
		}

		$cart_url = false;

		if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === get_class( $post_provider ) ) {
			$provider = tribe( 'tickets-plus.commerce.woo' );
		} elseif(
			'Tribe__Tickets_Plus__Commerce__EDD__Main' === get_class( $post_provider )
		) {
			$provider = tribe( 'tickets-plus.commerce.edd' );
		} else {
			return;
		}

		return $provider->get_cart_url();
	}


	public function get_cart_provider( $provider ) {
		if ( empty( $provider ) ) {
			return false;
		}

		switch ( $provider ) {
			case 'woo':
			case 'tribe_wooticket':
			case 'Tribe__Events__Tickets__Woo__Main':
				return tribe( 'tickets-plus.commerce.woo' );
				break;
			case 'edd':
			case 'tribe_eddticket':
			case 'Tribe__Events__Tickets__EDD__Main':
				return tribe( 'tickets-plus.commerce.edd' );
				break;
			case 'tpp':
			case 'tribe_tpp_attendees':
			case 'Tribe__Tickets__Commerce__PayPal__Main':
			return tribe( 'tickets.commerce.paypal' );
				break;
			default:
				return '';
				break;
		}

		/**
		 * Allow providers to include their own strings/suffixes.
		 *
		 * @since TBD
		 *
		 * @param array $provider_classes in format $provider -> class suffix.
		 */
		$provider_classes = apply_filters( 'tribe_attendee_registration_form_classes', [] );

		if ( array_key_exists( $provider, $provider_classes ) ) {
			$class = 'tribe-block__tickets__item__attendee__fields__form--' . $provider_classes[ $provider ];
		}
	}

	/**
	 * Given a provider, get the class to be applied to the attendee registration form
	 * @since 4.10.4
	 *
	 * @param string $provider the provider/attendee object name indicating ticket porovider
	 *
	 * @return string the class string or empty string if provider not found
	 */
	public function get_form_class( $provider ) {
		$class = '';

		if ( empty( $provider ) ) {
			/**
			 * Allows filterting the class before returning it in the case of no provider.
			 *
			 * @since 4.10.4
			 *
			 * @param string $class The (empty) class string.
			 */
			return apply_filters( 'tribe_attendee_registration_form_no_provider_class', $class );
		}

		/**
		 * Allow providers to include their own strings/suffixes.
		 *
		 * @since 4.10.4
		 *
		 * @param array $provider_classes in format $provider -> class suffix.
		 */
		$provider_classes = apply_filters( 'tribe_attendee_registration_form_classes', [] );

		if ( array_key_exists( $provider, $provider_classes ) ) {
			$class = 'tribe-block__tickets__item__attendee__fields__form--' . $provider_classes[ $provider ];
		}

		/**
		 * Allows filterting the class before returning it.
		 *
		 * @since 4.10.4
		 *
		 * @param string $class The class string.
		 */
		return apply_filters( 'tribe_attendee_registration_form_class', $class );
	}
}
