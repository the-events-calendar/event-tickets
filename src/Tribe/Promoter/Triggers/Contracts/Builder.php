<?php


namespace Tribe\Tickets\Promoter\Triggers\Contracts;

/**
 * Interface Builder
 *
 * @since TBD
 */
interface Builder {
	/**
	 * Build an attendee.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function create_attendee();

	/**
	 * Find the ticket instance.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function find_ticket();

	/**
	 * Find an event instance for this trigger message.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function find_event();
}