<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Errors
 *
 * An information repository for errors.
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Errors {

	public static function error_code_to_message( $error_code = '-1' ) {
		$map = array(
			'-1' => __( 'There was an error', 'event-tickets' ),
			'1'  => __( 'Attendee email and/or full name is missing', 'event-tickets' ),
			'2'  => __( 'Trying to oversell a ticket but the current oversell policy does not allow it', 'event-tickets' ),
			'3'  => __( 'Ticket quantity is 0', 'event-tickets' ),
		);

		/**
		 * Allows filtering the errors map.
		 *
		 * @since TBD
		 *
		 * @param array      $map        An associative array in the shape [ <error-code> => <error-message> ]
		 * @param int|string $error_code The current error code.
		 */
		$map = apply_filters( 'tribe_tickets_commerce_paypal_errors_map', $map, $error_code );

		return Tribe__Utils__Array::get( $map, $error_code, reset( $map ) );
	}
}
