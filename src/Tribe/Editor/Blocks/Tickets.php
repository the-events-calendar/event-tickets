<?php
/**
 * Tickets block Setup
 */
class Tribe__Tickets__Editor__Blocks__Tickets
extends Tribe__Editor__Blocks__Abstract {

	public function hook() {
		add_action( 'wp_ajax_ticket_availability_check', array( $this, 'ticket_availability' ) );
		add_action( 'wp_ajax_nopriv_ticket_availability_check', array( $this, 'ticket_availability' ) );
	}

	/**
	 * Which is the name/slug of this block
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function slug() {
		return 'tickets';
	}

	/**
	 * Since we are dealing with a Dynamic type of Block we need a PHP method to render it
	 *
	 * @since 4.9
	 *
	 * @param  array $attributes
	 *
	 * @return string
	 */
	public function render( $attributes = array() ) {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template           = tribe( 'tickets.editor.template' );
		$args['post_id']    = $post_id = $template->get( 'post_id', null, false );
		$args['attributes'] = $this->attributes( $attributes );

		// Prevent the render when the ID of the post has not being set to a correct value
		if ( $args['post_id'] === null ) {
			return;
		}

		// Fetch the default provider
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );
		if ( ! class_exists( $provider ) ) {
			return;
		}

		// If Provider is not active return
		if ( ! array_key_exists( $provider, Tribe__Tickets__Tickets::modules() ) ) {
			return;
		}

		$provider    = call_user_func( array( $provider, 'get_instance' ) );
		$provider_id = $this->get_provider_id( $provider );
		$tickets     = $this->get_tickets( $post_id );

		$args['provider']            = $provider;
		$args['provider_id']         = $provider_id;
		$args['cart_url']            = 'tpp' !== $provider_id ? $provider->get_cart_url() : '';
		$args['tickets_on_sale']     = $this->get_tickets_on_sale( $tickets );
		$args['has_tickets_on_sale'] = ! empty( $args['tickets_on_sale'] );
		$args['is_sale_past']        = $this->get_is_sale_past( $tickets );

		// Add the rendering attributes into global context
		$template->add_template_globals( $args );

		// enqueue assets
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-tickets' );
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-tickets-style' );

		return $template->template( array( 'blocks', $this->slug() ), $args, false );
	}

	/**
	 * Register block assets
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function assets() {
		$plugin    = Tribe__Tickets__Main::instance();
		$providers = tribe( 'tickets.data_api' )->get_providers_for_post( null );
		$currency  = tribe( 'tickets.commerce.currency' )->get_currency_config_for_provider( $providers, null );


		wp_register_script(
			'wp-utils',
			includes_url( '/js/wp-util.js' ),
			[ 'jquery', 'underscore' ],
			false,
			false
		);

		wp_enqueue_script('wp-utils');

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-tickets',
			'tickets-block.js',
			array( 'jquery', 'jquery-ui-datepicker', 'wp-utils', 'wp-i18n' ),
			null,
			[
				'type'         => 'js',
				'localize'     => [
					[
						'name' => 'TribeTickets',
						'data' => [
							'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
						],
					],
					[
						'name' => 'TribeCurrency',
						'data' => [
							'formatting' => json_encode( $currency ),
						],
					],
					[
						'name' => 'TribeCartEndpoint',
						'data' => [
							'url' => tribe_tickets_rest_url( '/cart/' )
						],
					],
					[
						'name' => 'TribeMessages',
						'data' => $this->set_messages(),
					]
				],
			]
		);
	}

	/**
	 * Check for ticket availability
	 *
	 * @since 4.9
	 *
	 * @param  array $tickets (IDs of tickets to check)
	 *
	 * @return void
	 */
	public function ticket_availability( $tickets = array() ) {

		$response  = array( 'html' => '' );
		$tickets   = tribe_get_request_var( 'tickets', array() );

		// Bail if we receive no tickets
		if ( empty( $tickets ) ) {
			wp_send_json_error( $response );
		}


		// Parse the tickets and create the array for the response
		foreach ( $tickets as $ticket_id ) {

			$ticket    = Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );
			$available = $ticket->available();
			$response['tickets'][ $ticket_id ]['available'] = $available;

			// If there are no more available we will send the template part HTML to update the DOM
			if ( 0 === $available ) {
				$response['tickets'][ $ticket_id ]['unavailable_html'] = tribe( 'tickets.editor.template' )->template( 'blocks/tickets/quantity-unavailable', $ticket, false );
			}
		}

		wp_send_json_success( $response );
	}

	/**
	 * Get all tickets for event/post, removing RSVPs
	 *
	 * @since 4.9
	 *
	 * @param  int $post_id Post ID
	 *
	 * @return array
	 */
	public function get_tickets( $post_id ) {
		$all_tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post_id );

		if ( ! $all_tickets ) {
			return array();
		}

		$tickets = array();

		foreach ( $all_tickets as $ticket ) {
			if ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
				continue;
			}

			$tickets[] = $ticket;
		}

		return $tickets;
	}

	/**
	 * Get provider ID
	 *
	 * @since 4.9
	 *
	 * @param  Tribe__Tickets__Tickets $provider Provider class instance
	 *
	 * @return string
	 */
	public function get_provider_id( $provider ) {

		switch ( $provider->class_name ) {
			case 'Tribe__Tickets__Commerce__PayPal__Main' :
				return 'tpp';
				break;
			case 'Tribe__Tickets_Plus__Commerce__WooCommerce__Main' :
				return 'woo';
				break;
			case 'Tribe__Tickets_Plus__Commerce__EDD__Main' :
				return 'edd';
				break;
			default:
				return 'tpp';
		}

	}

	/**
	 * Get all tickets on sale
	 *
	 * @since 4.9
	 *
	 * @param  array $tickets Array of all tickets
	 *
	 * @return array
	 */
	public function get_tickets_on_sale( $tickets ) {
		$tickets_on_sale = array();

		foreach ( $tickets as $ticket ) {
			if ( tribe_events_ticket_is_on_sale( $ticket ) ) {
				$tickets_on_sale[] = $ticket;
			}
		}

		return $tickets_on_sale;
	}

	/**
	 * Get whether all ticket sales have passed or not
	 *
	 * @since 4.9
	 *
	 * @param  array $tickets Array of all tickets
	 *
	 * @return bool
	 */
	public function get_is_sale_past( $tickets ) {
		$is_sale_past = ! empty( $tickets );

		foreach ( $tickets as $ticket ) {
			$is_sale_past = ( $is_sale_past && $ticket->date_is_later() );
		}

		return $is_sale_past;
	}

	/**
	 * Localized messages for errors, etc in javascript. Added in assets() above.
	 * Set up this way to amke it easier to add messages as needed.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function set_messages() {
		$messages = [
			'api_error_title'        => _x( 'API Connection Error', 'Error message title, will be followed by the error code.', 'event-tickets' ),
			'connection_error'       => __( 'Refresh this page or wait a few minutes before trying again. If this happens repeatedly, please contact the Site Admin.', 'event-tickets' ),
			'validation_error_title' => __( 'Whoops!', 'event-tickets' ),
			'validation_error'       => '<p>' . sprintf( _x( 'You have %s ticket(s) with a field that requires information.', 'The %s will change based on the error produced.', 'event-tickets' ), '<span class="tribe-tickets-notice--error__count">0</span>' ) . '</p>',
		];

		return $messages;
	}
}
