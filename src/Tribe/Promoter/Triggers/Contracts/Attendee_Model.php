<?php


namespace Tribe\Tickets\Promoter\Triggers\Contracts;

/**
 * Interface AttendeeModel
 *
 * @since TBD
 */
interface Attendee_Model {
	/**
	 * Validate all the requirements for this attendee so it can be controlled when the validation takes place.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function build();

	/**
	 * Return an array of values that represent all the required keys to be part of this attendee.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function required_fields();

	/**
	 * Return the ID of the attendee.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function id();

	/**
	 * Return the email associated with this attendee.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function email();

	/**
	 * Return the ID of the Event where the attendee was created.
	 *
	 * @return int
	 */
	public function event_id();

	/**
	 * Return the ID of the product associated to this attendee.
	 *
	 * @since TBD
	 *
	 * @return int
	 */
	public function product_id();

	/**
	 * Return the name of the ticket.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function ticket_name();
}