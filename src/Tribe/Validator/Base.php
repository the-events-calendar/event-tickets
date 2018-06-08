<?php

/**
 * Class Tribe__Events__Validator__Base
 *
 * @since TBD
 */
class Tribe__Tickets__Validator__Base extends Tribe__Validator__Base
	implements Tribe__Tickets__Validator__Interface {

	/**
	 * whether the value is the post id of an existing ticket or not.
	 *
	 * @since tbd
	 *
	 * @param int $ticket_id
	 *
	 * @return bool
	 */
	public function is_ticket_id( $ticket_id ) {
		if ( empty( $ticket_id ) ) {
			return false;
		}

		// get ticket provider
		$ticket_type = tribe( 'tickets.data_api' )->detect_by_id( $ticket_id );

		//get ticket
		$ticket = get_post( $ticket_id );

		return ! empty( $ticket_type['post_type'] ) && ! empty( $ticket ) &&  $ticket_type['post_type'] === $ticket->post_type;
	}

	/**
	 * Whether the value is the post ID of an existing event or not.
	 *
	 * @since TBD
	 *
	 * @param int|string $event_id
	 *
	 * @return bool
	 */
	public function is_event_id( $event_id ) {
		if ( empty( $event_id ) ) {
			return false;
		}

		$event = get_post( $event_id );

		return ! empty( $event ) && 'tribe_event' === $event->post_type;
	}

}
