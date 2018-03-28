<?php

/**
 * Class Tribe__Events__Validator__Base
 *
 * @since TBD
 */
class Tribe__Tickets__Validator__Base extends Tribe__Validator__Base
	implements Tribe__Tickets__Validator__Interface {

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
