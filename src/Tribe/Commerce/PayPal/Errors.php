<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Errors
 *
 * An information repository for errors.
 *
 * @since 4.7
 */
class Tribe__Tickets__Commerce__PayPal__Errors {

	/**
	 * Casts a numeric error code related to PayPal tickets to a localized string.
	 *
	 * @since 4.7
	 *
	 * @param string|int $error_code
	 *
	 * @return string
	 */
	public static function error_code_to_message( $error_code = '-1' ) {
		$map = array(
			'-1'  => __( 'There was an error', 'event-tickets' ),
			'1'   => __( 'Attendee email and/or full name is missing', 'event-tickets' ),
			'2'   => __( 'Trying to oversell a ticket but the current oversell policy does not allow it', 'event-tickets' ),
			'3'   => __( 'Ticket quantity is 0', 'event-tickets' ),

			// a numeric namespace reserved for front-end errors
			'101' => __( 'In order to purchase tickets, you must enter your name and a valid email address.', 'event-tickets' ),
			'102' => __( 'You can\'t add more tickets than the total remaining tickets.', 'event-tickets' ),
			'103' => __( 'You should add at least one ticket.', 'event-tickets' ),

			// a numeric namespace reserved for front-end messages
			'201' => __( "Your order is currently processing. Once completed, you'll receive your ticket(s) in an email.", 'event-tickets' ),
		);

		/**
		 * Allows filtering the errors map.
		 *
		 * @since 4.7
		 *
		 * @param array      $map        An associative array in the shape [ <error-code> => <error-message> ]
		 * @param int|string $error_code The current error code.
		 */
		$map = apply_filters( 'tribe_tickets_commerce_paypal_errors_map', $map, $error_code );

		return Tribe__Utils__Array::get( $map, $error_code, reset( $map ) );
	}
}
