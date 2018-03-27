<?php


interface Tribe__Tickets__Validator__Interface extends Tribe__Validator__Interface {

	/**
	 * Whether the value is the post ID of an existing event or not.
	 *
	 * @param int|string $event_id
	 *
	 * @return bool
	 */
	public function is_event_id( $event_id );

}
