<?php


interface Tribe__Tickets__Validator__Interface extends Tribe__Validator__Interface {

	/**
	 * whether the value is the post id of an existing ticket or not.
	 *
	 * @since tbd
	 *
	 * @param int $ticket_id
	 *
	 * @return bool
	 */
	public function is_ticket_id( $ticket_id );

	/**
	 * Whether the value is the post ID of an existing event or not.
	 *
	 * @since TBD
	 *
	 * @param int|string $event_id
	 *
	 * @return bool
	 */
	public function is_event_id( $event_id );

}
