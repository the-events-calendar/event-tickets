<?php
class Tribe__Tickets__Editor__Blocks__Rsvp
extends Tribe__Editor__Blocks__Abstract {

	/**
	 * Init class
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function hook() {
		// Add AJAX calls
		add_action( 'wp_ajax_rsvp-form', array( $this, 'rsvp_form' ) );
		add_action( 'wp_ajax_nopriv_rsvp-form', array( $this, 'rsvp_form' ) );
		add_action( 'wp_ajax_rsvp-process', array( $this, 'rsvp_process' ) );
		add_action( 'wp_ajax_nopriv_rsvp-process', array( $this, 'rsvp_process' ) );

	}

	/**
	 * Which is the name/slug of this block
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function slug() {
		return 'rsvp';
	}

	/**
	 * Set the default attributes of this block
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	public function default_attributes() {

		$defaults = array();

		return $defaults;
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
		$template                 = tribe( 'tickets.editor.template' );
		$args['post_id']          = $post_id = $template->get( 'post_id', null, false );
		$rsvps                    = $this->get_tickets( $post_id );
		$args['attributes']       = $this->attributes( $attributes );
		$args['active_rsvps']     = $this->get_active_tickets( $rsvps );
		$args['has_active_rsvps'] = ! empty( $args['active_rsvps'] );
		$args['has_rsvps']        = ! empty( $rsvps );
		$args['all_past']         = $this->get_all_tickets_past( $rsvps );

		// Add the rendering attributes into global context
		$template->add_template_globals( $args );

		// enqueue assets
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-rsvp' );
		tribe_asset_enqueue( 'tribe-tickets-gutenberg-block-rsvp-style' );

		return $template->template( array( 'blocks', $this->slug() ), $args, false );
	}

	/**
	 * Method to get all RSVP tickets
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	protected function get_tickets( $post_id ) {
		$tickets = array();

		// Bail if there's no event id
		if ( ! $post_id ) {
			return $tickets;
		}

		// Get the tickets IDs for this event
		$ticket_ids = tribe( 'tickets.rsvp' )->get_tickets_ids( $post_id );

		// Bail if we don't have tickets
		if ( ! $ticket_ids ) {
			return $tickets;
		}

		foreach ( $ticket_ids as $post ) {
			// Get the ticket
			$ticket = tribe( 'tickets.rsvp' )->get_ticket( $post_id, $post );

			// Continue if is not RSVP, we only want RSVP tickets
			if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
				continue;
			}

			$tickets[] = $ticket;
		}

		return $tickets;
	}

	/**
	 * Method to get the active RSVP tickets
	 *
	 * @since 4.9
	 *
	 * @return array
	 */
	protected function get_active_tickets( $tickets ) {
		$active_tickets = array();

		foreach ( $tickets as $ticket ) {
			// continue if it's not in date range
			if ( ! $ticket->date_in_range() ) {
				continue;
			}

			$active_tickets[] = $ticket;
		}

		return $active_tickets;
	}

	/**
	 * Method to get the all RSVPs past flag
	 * All RSVPs past flag is true if all RSVPs end date is earlier than current date
	 * If there are no RSVPs, false is returned
	 *
	 * @since 4.9
	 *
	 * @return bool
	 */
	protected function get_all_tickets_past( $tickets ) {
		if ( empty( $tickets ) ) {
			return false;
		}

		$all_past = true;

		foreach ( $tickets as $ticket ) {
			$all_past = $all_past && $ticket->date_is_later();
		}

		return $all_past;
	}

	/**
	 * Register block assets
	 *
	 * @since 4.9
	 *
	 * @param  array $attributes
	 *
	 * @return void
	 */
	public function assets() {
		$plugin = Tribe__Tickets__Main::instance();

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-rsvp',
			'rsvp-block.js',
			array( 'jquery', 'jquery-ui-datepicker' ),
			null,
			array(
				'localize'     => array(
					'name' => 'TribeRsvp',
					'data' => array(
						'ajaxurl' => admin_url( 'admin-ajax.php', ( is_ssl() ? 'https' : 'http' ) ),
					),
				),
			)
		);

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-block-rsvp-style',
			'app/rsvp/frontend.css',
			array(),
			null
		);

	}

	/**
	 * Function that returns the RSVP form from an AJAX call
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function rsvp_form() {

		$response  = array( 'html' => '', 'view' => 'rsvp-form' );
		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );
		$going     = tribe_get_request_var( 'going', 'yes' );

		if ( 0 === $ticket_id ) {
			wp_send_json_error( $response );
		}

		$args = array(
			'ticket_id' => $ticket_id,
			'ticket'    => tribe( 'tickets.rsvp' )->get_ticket( get_the_id(), $ticket_id ),
			'going'     => $going,
		);

		$html = tribe( 'tickets.editor.template' )->template( 'blocks/rsvp/form/form', $args, false );

		$response['html']    = $html;

		wp_send_json_success( $response );

	}

	/**
	 * Function that process the RSVP
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function rsvp_process() {

		$response  = array( 'html' => '', 'view' => 'rsvp-process' );
		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );

		if ( 0 === $ticket_id ) {
			wp_send_json_error( $response );
		}
		
		$has_tickets    = false;
		$event          = tribe( 'tickets.rsvp' )->get_event_for_ticket( $ticket_id );
		$post_id        = $event->ID;
		$ticket         = tribe( 'tickets.rsvp' )->get_ticket( $post_id, $ticket_id );

		/**
		 * RSVP specific action fired just before a RSVP-driven attendee tickets for an order are generated
		 *
		 * @param $data $_POST Parameters comes from RSVP Form
		 */
		do_action( 'tribe_tickets_rsvp_before_order_processing' );

		$attendee_details = tribe( 'tickets.rsvp' )->parse_attendee_details();

		if ( false === $attendee_details ) {
			wp_send_json_error( $response );
		}

		$products = (array) tribe_get_request_var( 'product_id' );

		// Iterate over each product
		foreach ( $products as $product_id ) {
			if ( ! $ticket_qty = tribe( 'tickets.rsvp' )->parse_ticket_quantity( $product_id ) ) {
				// if there were no RSVP tickets for the product added to the cart, continue
				continue;
			}

			$has_tickets |= tribe( 'tickets.rsvp' )->generate_tickets_for( $product_id, $ticket_qty, $attendee_details );
		}

		$order_id              = $attendee_details['order_id'];
		$attendee_order_status = $attendee_details['order_status'];

		/**
		 * Fires when an RSVP attendee tickets have been generated.
		 *
		 * @param int    $order_id              ID of the RSVP order
		 * @param int    $post_id               ID of the post the order was placed for
		 * @param string $attendee_order_status 'yes' if the user indicated they will attend
		 */
		do_action( 'event_tickets_rsvp_tickets_generated', $order_id, $post_id, $attendee_order_status );

		$send_mail_stati = array( 'yes' );

		/**
		 * Filters whether a confirmation email should be sent or not for RSVP tickets.
		 *
		 * This applies to attendance and non attendance emails.
		 *
		 * @param bool $send_mail Defaults to `true`.
		 */
		$send_mail = apply_filters( 'tribe_tickets_rsvp_send_mail', true );

		if ( $send_mail ) {
			/**
			 * Filters the attendee order stati that should trigger an attendance confirmation.
			 *
			 * Any attendee order status not listed here will trigger a non attendance email.
			 *
			 * @param array  $send_mail_stati       An array of default stati triggering an attendance email.
			 * @param int    $order_id              ID of the RSVP order
			 * @param int    $post_id               ID of the post the order was placed for
			 * @param string $attendee_order_status 'yes' if the user indicated they will attend
			 */
			$send_mail_stati = apply_filters(
				'tribe_tickets_rsvp_send_mail_stati',
				$send_mail_stati,
				$order_id,
				$post_id,
				$attendee_order_status
			);

			// No point sending tickets if their current intention is not to attend
			if ( $has_tickets && in_array( $attendee_order_status, $send_mail_stati ) ) {
				tribe( 'tickets.rsvp' )->send_tickets_email( $order_id, $post_id );
			} elseif ( $has_tickets ) {
				tribe( 'tickets.rsvp' )->send_non_attendance_confirmation( $order_id, $post_id );
			}
		}

		$args = array(
			'ticket_id' => $ticket_id,
			'ticket'    => $ticket,
		);

		$remaining = $ticket->remaining();

		if ( ! $remaining ) {
			$response['status_html'] = tribe( 'tickets.editor.template' )->template( 'blocks/rsvp/status', $args, false );
		}

		$response['remaining']      = $ticket->remaining();
		$response['remaining_html'] = tribe( 'tickets.editor.template' )->template( 'blocks/rsvp/details/availability', $args, false );
		$response['html']           = tribe( 'tickets.editor.template' )->template( 'blocks/rsvp/messages/success', $args, false );

		wp_send_json_success( $response );

	}
}
