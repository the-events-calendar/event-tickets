<?php

/**
 * Class Tribe__Events__Validator__Base
 *
 * @since TBD
 */
class Tribe__Tickets__Validator__Base extends Tribe__Validator__Base
	implements Tribe__Tickets__Validator__Interface {

	/**
	 * {@inheritdoc}
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
	 * {@inheritdoc}
	 */
	public function is_event_id( $event_id ) {
		if ( empty( $event_id ) ) {
			return false;
		}

		$event = get_post( $event_id );

		return ! empty( $event ) && 'tribe_event' === $event->post_type;
	}

	/**
	 * Whether a post ID exists.
	 *
	 * @since TBD
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 */
	public function is_post_id( $post_id ) {
		$post = get_post( $post_id );

		return ( $post instanceof WP_Post );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_post_id_list( $posts, $sep = ',' ) {
		$sep   = is_string( $sep ) ? $sep : ',';
		$posts = Tribe__Utils__Array::list_to_array( $posts, $sep );

		$valid = array_filter( $posts, array( $this, 'is_post_id' ) );

		return ! empty( $valid ) && count( $valid ) === count( $posts );
	}

	/**
	 * Whether the value is the post id of an existing attendee or not.
	 *
	 * @since tbd
	 *
	 * @param int $attendee_id
	 *
	 * @return bool
	 */
	public function is_attendee_id( $attendee_id ) {
		if ( empty( $attendee_id ) ) {
			return false;
		}

		// get ticket provider
		$ticket_type = tribe( 'tickets.data_api' )->detect_by_id( $attendee_id );

		//get ticket
		$ticket = get_post( $attendee_id );

		return ! empty( $ticket_type['post_type'] ) && ! empty( $ticket ) &&  $ticket_type['post_type'] === $ticket->post_type;
	}
}
