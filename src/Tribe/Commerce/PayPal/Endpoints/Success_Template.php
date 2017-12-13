<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Endpoints__Success_Template
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Endpoints__Success_Template implements Tribe__Tickets__Commerce__PayPal__Endpoints__Template_Interface {

	/**
	 * @var string The PayPal order identification string.
	 */
	protected $order_number;

	/**
	 * Registers the resources this template will need to correctly render.
	 */
	public function register_resources() {
		// no-op
	}

	/**
	 * Enqueues the resources needed by this template to correctly render.
	 *
	 * @since TBD
	 */
	public function enqueue_resources() {
		Tribe__Tickets__RSVP::get_instance()->enqueue_resources();
	}

	/**
	 * Renders and returns the template rendered contents.
	 *
	 * @since TBD
	 *
	 * @param array $template_data
	 *
	 * @return string
	 */
	public function render( array $template_data = array() ) {
		$template_data = $this->get_template_data( $template_data );

		$is_just_visiting       = $template_data['is_just_visiting'];
		$order_is_valid         = $template_data['order_is_valid'];
		$order_is_not_completed = $template_data['order_is_not_completed'];

		if ( $order_is_not_completed ) {
			$order  = $template_data['order'];
			$status = $template_data['status'];
		} elseif ( $order_is_valid ) {
			$purchaser_name  = $template_data['purchaser_name'];
			$purchaser_email = $template_data['purchaser_email'];
			$tickets         = $template_data['tickets'];
			$order           = $template_data['order'];
		}

		ob_start();
		include Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/tpp-success.php' );

		return ob_get_clean();
	}

	/**
	 * Builds and returns the date needed by this template.
	 *
	 * @since TBD
	 *
	 * @param array $template_data
	 *
	 * @return array
	 */
	public function get_template_data( array $template_data = array() ) {
		/** @var \Tribe__Tickets__Commerce__PayPal__Main $paypal */
		$paypal                                  = tribe( 'tickets.commerce.paypal' );
		$template_data['is_just_visiting']       = false;
		$template_data['order_is_valid']         = true;
		$template_data['order_is_not_completed'] = false;
		$order_number                            = Tribe__Utils__Array::get( $_GET, 'tribe-tpp-order', false );
		$attendees                               = $paypal->get_attendees_by_order_id( $order_number );

		if ( empty( $attendees ) ) {
			// the order might have not been processed yet
			if ( ! isset( $_GET['tx'], $_GET['st'] ) ) {
				// this might just be someone visiting the page, all the pieces are missing
				$template_data['is_just_visiting'] = true;

				return $template_data;
			}

			if ( isset( $_GET['tx'], $_GET['st'] ) ) {
				// transaction and status details are set
				$defaults = array( 'user_id' => get_current_user_id(), 'tribe_handler' => 'tpp' );
				$custom   = wp_parse_args( (array) json_decode( Tribe__Utils__Array::get( $_GET, 'cm', array() ), true ), $defaults );

				$template_data['order_is_not_completed'] = true;
				$template_data['order']                  = $_GET['tx'];
				$template_data['status']                 = trim( strtolower( $_GET['st'] ) );

				return $template_data;
			}

			// we are missing one of the pieces...
			$template_data['order_is_valid'] = false;

			return $template_data;
		}

		// the purchaser details will be the same for all the attendees, so we fetch it from the first
		$first                            = reset( $attendees );
		$template_data['purchaser_name']  = get_post_meta( $first->ID, $paypal->full_name, true );
		$template_data['purchaser_email'] = get_post_meta( $first->ID, $paypal->email, true );

		$order_quantity = $order_total = 0;
		$tickets        = array();

		foreach ( $attendees as $attendee ) {
			$order_quantity ++;
			$ticket_id    = get_post_meta( $attendee->ID, $paypal->attendee_product_key, true );
			$post_id      = get_post_meta( $attendee->ID, $paypal->attendee_event_key, true );
			$ticket_price = (int) get_post_meta( $ticket_id, '_price', true );
			$order_total  += $ticket_price;

			if ( array_key_exists( $ticket_id, $tickets ) ) {
				$tickets[ $ticket_id ]['quantity'] += 1;
				$tickets[ $ticket_id ]['subtotal'] = $tickets[ $ticket_id ]['quantity'] * $ticket_price;
			} else {
				$header_image_id       = ! empty( $post_id )
					? tribe( 'tickets.handler' )->get_header_image_id( $post_id )
					: false;
				$tickets[ $ticket_id ] = array(
					'name'            => get_the_title( $ticket_id ),
					'price'           => $ticket_price,
					'quantity'        => 1,
					'subtotal'        => $ticket_price,
					'post_id'         => $post_id,
					'is_event'        => function_exists( 'tribe_is_event' ) && tribe_is_event( $post_id ),
					'header_image_id' => $header_image_id,
				);
			}
		}

		$template_data['order']   = array( 'quantity' => $order_quantity, 'total' => $order_total );
		$template_data['tickets'] = $tickets;

		return $template_data;
	}
}