<?php

namespace Tribe\Tickets\Promoter\Triggers\Contracts;

use RuntimeException;
use Tribe__Tickets__Ticket_Object;
use WP_Post;

/**
 * Interface Triggered
 *
 * @since TBD
 */
interface Triggered {
	/**
	 * Return an instance to the WP_Post object.
	 *
	 * @since TBD
	 *
	 * @return WP_Post
	 */
	public function post();

	/**
	 * Return the type of trigger message as a label.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function type();

	/**
	 * Build the trigger messages with all the requirements.
	 *
	 * @since TBD
	 *
	 * @throws RuntimeException
	 *
	 * @return mixed
	 */
	public function build();

	/**
	 * Get access to the instance of the ticket associated with the trigger.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__Ticket_Object
	 */
	public function ticket();

	/**
	 * Access to the attendee associated with the trigger message.
	 *
	 * @since TBD
	 *
	 * @return AttendeeModel
	 */
	public function attendee();
}