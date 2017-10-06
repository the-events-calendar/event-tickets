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
		$paypal                          = tribe( 'tickets.commerce.paypal' );
		$template_data['order_is_valid'] = true;
		$order_number                    = Tribe__Utils__Array::get( $_GET, 'tribe-tpp-order', false );
		$attendees                       = $paypal->get_attendees_by_order( $order_number );
		if ( empty( $attendees ) ) {
			// weird...
			$template_data['order_is_valid'] = false;

			return;
		}

		$template_data['post_id'] = Tribe__Utils__Array::get( $template_data, 'post_id', $paypal->get_post_id_from_order( $order_number ) );

		// the purchaser details will be the same for all the attendees, so we fetch it from the first
		$first                            = reset( $attendees );
		$template_data['purchaser_name']  = get_post_meta( $first->ID, $paypal->full_name, true );
		$template_data['purchaser_email'] = get_post_meta( $first->ID, $paypal->email, true );

		$order_quantity = $order_total = 0;
		$tickets        = array();

		foreach ( $attendees as $attendee ) {
			$order_quantity ++;
			$ticket_id      = get_post_meta( $attendee->ID, $paypal->attendee_product_key, true );
			$ticket_post_id = get_post_meta( $attendee->ID, $paypal->attendee_event_key, true );
			$ticket_price   = (int) get_post_meta( $ticket_id, '_price', true );
			$order_total    += $ticket_price;

			if ( array_key_exists( $ticket_id, $tickets ) ) {
				$tickets[ $ticket_id ]['quantity'] += 1;
				$tickets[ $ticket_id ]['subtotal'] = $tickets[ $ticket_id ]['quantity'] * $ticket_price;
			} else {
				$header_image_id       = ! empty( $ticket_post_id )
					? tribe( 'tickets.handler' )->get_header_image_id( $ticket_post_id )
					: false;
				$tickets[ $ticket_id ] = array(
					'name'            => get_the_title( $ticket_id ),
					'price'           => $ticket_price,
					'quantity'        => 1,
					'subtotal'        => $ticket_price,
					'post_id'         => $ticket_post_id,
					'header_image_id' => $header_image_id,
				);
			}
		}

		$template_data['order']    = array( 'quantity' => $order_quantity, 'total' => $order_total );
		$template_data['tickets']  = $tickets;
		$template_data['is_event'] = function_exists( 'tribe_is_event' ) && tribe_is_event( $template_data['post_id'] );

		return $template_data;
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

		$order_is_valid = $template_data['order_is_valid'];

		if ( $order_is_valid ) {
			$post_id         = $template_data['post_id'];
			$purchaser_name  = $template_data['purchaser_name'];
			$purchaser_email = $template_data['purchaser_email'];
			$is_event        = $template_data['is_event'];
			$tickets         = $template_data['tickets'];
			$order           = $template_data['order'];
		}

		ob_start();
		include Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/tpp-success.php' );

		return ob_get_clean();
	}
}