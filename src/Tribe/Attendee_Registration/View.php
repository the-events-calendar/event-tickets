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
	 * @since 4.12.0 Removed $content and $context parameters
	 *
	 * @return string The resulting template content
	 */
	public function display_attendee_registration_page() {
		return $this->display_attendee_registration_shortcode();
	}

	/**
	 * Render the Attendee Info shortcode.
	 *
	 * @since 4.12.0
	 * @since 4.12.3 Get provider slug more consistently.
	 *
	 * @return string The resulting template content
	 */
	public function display_attendee_registration_shortcode() {
		$q_provider = tribe_get_request_var( 'provider', false );

		/**
		 * Filter to add/remove tickets from the global cart
		 *
		 * @since 4.9
		 * @since 4.11.0 Added $q_provider to allow context of current provider.
		 *
		 * @param array  $tickets_in_cart The array containing the cart elements. Format array( 'ticket_id' => 'quantity' ).
		 * @param string $q_provider      Current ticket provider.
		 */
		$tickets_in_cart = apply_filters( 'tribe_tickets_tickets_in_cart', [], $q_provider );

		$events           = [];
		$providers        = [];
		$default_provider = [];
		$non_meta_count   = 0;

		foreach ( $tickets_in_cart as $ticket_id => $quantity ) {
			// Load the tickets in cart for each event, with their ID, quantity and provider.

			/** @var Tribe__Tickets__Tickets_Handler $handler */
			$handler = tribe( 'tickets.handler' );
			$ticket  = $handler->get_object_connections( $ticket_id );

			if ( ! $ticket->provider instanceof Tribe__Tickets__Tickets ) {
				continue;
			}

			$has_meta = get_post_meta( $ticket_id, '_tribe_tickets_meta_enabled', true );

			if ( empty( $has_meta ) || ! tribe_is_truthy( $has_meta ) ) {
				$non_meta_count += $quantity;
			}

			$ticket_providers = [ $ticket->provider->attendee_object ];

			if ( ! empty( $ticket->provider->orm_provider ) ) {
				$ticket_providers[] = $ticket->provider->orm_provider;
			}

			// If we've got a provider and it doesn't match, skip the ticket.
			if ( ! in_array( $q_provider, $ticket_providers, true ) ) {
				continue;
			}

			$ticket_data = [
				'id'       => $ticket_id,
				'qty'      => $quantity,
				'provider' => $ticket->provider,
			];

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

			if ( empty( $default_provider ) ) {
				// One provider per instance.
				$default_provider[ $q_provider ] = $ticket->provider->class_name;
			}

			/** @var Tribe__Tickets__Status__Manager $status */
			$status   = tribe( 'tickets.status' );
			$provider = $status->get_provider_slug( $ticket->provider->class_name );

			$providers[ $ticket->event ] = $provider;
			$events[ $ticket->event ][]  = $ticket_data;
		}

		/**
		 * Check if the cart has a ticket with required meta fields
		 *
		 * @since TDB
		 *
		 * @param boolean $cart_has_required_meta Whether the cart has required meta.
		 * @param array   $tickets_in_cart        The array containing the cart elements. Format array( 'ticket_id' => 'quantity' ).
		 */
		$cart_has_required_meta = (bool) apply_filters( 'tribe_tickets_attendee_registration_has_required_meta', ! empty( $tickets_in_cart ), $tickets_in_cart );

		// Get the checkout URL, it'll be added to the checkout button.

		/** @var Tribe__Tickets__Attendee_Registration__Main $attendee_registration */
		$attendee_registration = tribe( 'tickets.attendee_registration' );

		$checkout_url = $attendee_registration->get_checkout_url();

		/**
		 * Filter to check if there's any required meta that wasn't filled in
		 *
		 * @since TDB
		 *
		 * @param bool
		 */
		$is_meta_up_to_date = (int) apply_filters( 'tribe_tickets_attendee_registration_is_meta_up_to_date', true );

		// Enqueue styles and scripts for this page.
		tribe_asset_enqueue_group( 'tribe-tickets-registration-page' );

		// One provider per instance.
		$currency        = tribe( 'tickets.commerce.currency' );
		$currency_config = tribe( 'tickets.commerce.currency' )->get_currency_config_for_provider( $default_provider, null );

		/**
		 *  Set all the template variables
		 */
		$args = [
			'events'                 => $events,
			'checkout_url'           => $checkout_url,
			'is_meta_up_to_date'     => $is_meta_up_to_date,
			'cart_has_required_meta' => $cart_has_required_meta,
			'providers'              => $providers,
			'currency'               => $currency,
			'currency_config'        => $currency_config,
			'is_modal'               => null,
			'provider'               => $this->get( 'provider' ) ?: tribe_get_request_var( 'provider' ),
			'non_meta_count'         => $non_meta_count,
		];

		wp_localize_script(
			'tribe-tickets-registration-page-scripts',
			'TribeCurrency',
			[ 'formatting' => json_encode( $currency_config ) ]
		);
		wp_localize_script(
			'tribe-tickets-registration-page-scripts',
			'TribeCartEndpoint',
			[ 'url' => tribe_tickets_rest_url( '/cart/' ) ]
		);

		wp_enqueue_style( 'dashicons' );

		$this->add_template_globals( $args );

		// Check wether we use v1 or v2. We need to update this when we deprecate tickets v1.
		$template_path = tribe_tickets_new_views_is_enabled() ? 'v2/attendee-registration/content' : 'registration-js/content';

		return $this->template( $template_path, $args, false );
	}

	/**
	 * Get the provider Cart URL.
	 *
	 * @since 4.9
	 *
	 * @param string $provider Provider identifier.
	 *
	 * @return bool|string
	 */
	public function get_cart_url( $provider ) {
		if ( is_numeric( $provider ) ) {
			/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
			$tickets_handler = tribe( 'tickets.handler' );
			$provider        = get_post_meta( absint( $provider ), $tickets_handler->key_provider_field, true );
		}

		if ( empty( $provider ) ) {
			return false;
		}

		$post_provider = $this->get_cart_provider( $provider );

		if ( empty( $post_provider ) ) {
			return false;
		}

		try {
			if ( 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' === get_class( $post_provider ) ) {
				/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $provider */
				$provider = tribe( 'tickets-plus.commerce.woo' );
			} elseif ( 'Tribe__Tickets_Plus__Commerce__EDD__Main' === get_class( $post_provider ) ) {
				/** @var \Tribe__Tickets_Plus__Commerce__EDD__Main $provider */
				$provider = tribe( 'tickets-plus.commerce.edd' );
			} else {
				return;
			}
		} catch ( RuntimeException $exception ) {
			return;
		}

		if ( ! $provider instanceof Tribe__Tickets__Tickets ) {
			return false;
		}

		return $provider->get_cart_url();
	}


	/**
	 * Get the cart provider class/object.
	 *
	 * @since 4.11.0
	 * @since 4.12.3 Check if provider is a proper object and is active.
	 *
	 * @param string $provider A string indicating the desired provider.
	 *
	 * @return boolean|object The provider object or boolean false if none found.
	 */
	public function get_cart_provider( $provider ) {
		if ( empty( $provider ) ) {
			return false;
		}

		$provider_obj = false;

		/**
		 * Allow providers to include themselves if they are not in the above.
		 *
		 * @since 4.11.0
		 *
		 * @param string $provider A string indicating the desired provider.
		 *
		 * @return boolean|object The provider object or boolean false if none found above.
		 */
		$provider_obj = apply_filters( 'tribe_attendee_registration_cart_provider', $provider_obj, $provider );

		if (
			! $provider_obj instanceof Tribe__Tickets__Tickets
			|| ! tribe_tickets_is_provider_active( $provider_obj )
		) {
			$provider_obj = false;
		}

		return $provider_obj;
	}

	/**
	 * Given a provider, get the class to be applied to the attendee registration form.
	 *
	 * @since 4.10.4
	 * @since 4.12.3 Consolidate getting provider.
	 *
	 * @param string|Tribe__Tickets__Tickets $provider The provider/attendee object name indicating ticket provider.
	 *
	 * @return string The class string or empty string if provider not found or not active.
	 */
	public function get_form_class( $provider ) {
		$class = '';

		if ( is_string( $provider ) ) {
			$provider = Tribe__Tickets__Tickets::get_ticket_provider_instance( $provider );
		}

		if ( ! empty( $provider ) ) {
			$provider = $provider->attendee_object;
		}

		if ( empty( $provider ) ) {
			/**
			 * Allows filtering the class before returning it in the case of no provider.
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
		 * @param array $provider_classes In the format of: $provider -> class suffix.
		 */
		$provider_classes = apply_filters( 'tribe_attendee_registration_form_classes', [] );

		if ( array_key_exists( $provider, $provider_classes ) ) {
			$class = 'tribe-tickets__item__attendee__fields__form--' . $provider_classes[ $provider ];
		}

		/**
		 * Allows filtering the class before returning it.
		 *
		 * @since 4.10.4
		 *
		 * @param string $class The class string.
		 */
		return apply_filters( 'tribe_attendee_registration_form_class', $class );
	}
}
