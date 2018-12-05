<?php
/**
 * Tickets block Setup
 */
class Tribe__Tickets__Editor__Blocks__Tickets
extends Tribe__Editor__Blocks__Abstract {

	public function hook() {
		add_action( 'wp_ajax_ticket_availability_check', array( $this, 'ticket_availability' ) );
		add_action( 'wp_ajax_nopriv_ticket_availability_check', array( $this, 'ticket_availability' ) );

		add_shortcode( 'gutti_tickets_purchase', array( $this, 'render_shortcode_attendees' ) );
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
	 * Returns the Correct tickets for the Tickets block
	 *
	 * @since 4.9
	 *
	 * @param  int   $post_id  Which Event or Post we are looking ticket in
	 * @return array
	 */
	private function get_tickets( $post_id ) {
		$unfiltered_tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post_id );
		$tickets = array();

		foreach ( $unfiltered_tickets as $key => $ticket ) {
			// Skip RSVP items
			if ( 'Tribe__Tickets__RSVP' === $ticket->provider_class ) {
				continue;
			}

			if ( ! $ticket->date_in_range() ) {
				continue;
			}

			$tickets[] = $ticket;
		}

		return $tickets;
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
		$args['tickets']    = $this->get_tickets( $post_id );

		// Prevent the render when the ID of the post has not being set to a correct value
		if ( $args['post_id'] === null ) {
			return;
		}

		// Fetch the default provider
		$provider    = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );
		$provider    = call_user_func( array( $provider, 'get_instance' ) );
		$provider_id = $this->get_provider_id( $provider );

		$args['provider']    = $provider;
		$args['provider_id'] = $provider_id;
		$args['cart_url']    = 'tpp' !== $provider_id ? $provider->get_cart_url() : '';

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
	 *
	 * @return void
	 */
	public function assets() {
		$plugin = Tribe__Tickets__Main::instance();

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-tickets',
			'views/tickets.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			null,
			array(
				'type'         => 'js',
				'localize'     => array(
					'name' => 'TribeTickets',
					'data' => array(
						'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
					),
				),
			)
		);

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-block-tickets-style',
			'app/tickets/frontend.css',
			array(),
			null
		);
	}

	/**
	 * A nice shortcode to show the WIP for the
	 * Registration intermediate page & styles.
	 *
	 * THIS IS A WIP AND THE PURPOSE IS ONLY TO SHOW THE
	 * RESULTS
	 *
	 * USE THE SHORTCODE [gutti_tickets_purchase ticket="ID"]
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function render_shortcode_attendees( $atts, $content = '' ) {

		// Bail if we don't receive `ticket` with the ticket id as a param
		if ( ! isset( $atts['ticket'] ) ) {
			return $content;
		}

		$ticket_id = intval( $atts['ticket'] );
		$ticket    = Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );

		// Initialize attributes, in case we need them for the WIP
		$attributes = array(
			'ticket_id' => $ticket_id,
			'ticket'    => $ticket, // just in case, to test
		);

		// enqueue assets
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-tickets-style' );

		// set arguments. Let's just use the ticket we receive as the shortcode argument
		// to display the results
		$args['tickets'][]  = $ticket;
		$args['attributes'] = $this->attributes( $attributes );

		// Add the rendering attributes into global context
		tribe( 'tickets.editor.template' )->add_template_globals( $args );

		$content = tribe( 'tickets.editor.template' )->template( 'blocks/tickets/registration/content', $args, false );

		return $content;
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
}
