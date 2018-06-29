<?php


interface Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * Returns the array representation of a ticket.
	 *
	 * @since TBD
	 *
	 * @param int|WP_Post|array|Tribe__Tickets__Ticket_Object $ticket_id A ticket post, data, post ID or object.
	 * @param string                                          $context   Context of data.
	 *
	 * @return array|WP_Error ticket data or a `WP_Error` detailing the issue on failure.
	 */
	public function get_ticket_data( $ticket_id, $context = 'public' );

	/**
	 * Returns an attendee data.
	 *
	 * @since  TBD
	 *
	 * @param int $attendee_id An attendee post or post ID.
	 * @param string $context Context of data.
	 *
	 * @return array|WP_Error The attendee data or a `WP_Error` detailing the issue on failure.
	 */
	public function get_attendee_data( $attendee_id, $context = '' );

	/**
	 * Sets the data context the repository should be aware of.
	 *
	 * @param string $context
	 */
	public function set_context( $context );

	/**
	 * Returns the slug for provider.
	 *
	 * @since TBD
	 *
	 * @param string|object $provider_class The provider object or class.
	 *
	 * @return string
	 */
	public function get_provider_slug( $provider_class );
}