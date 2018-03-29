<?php


class Tribe__Tickets__REST__V1__Messages implements Tribe__REST__Messages_Interface {

	/**
	 * @var string
	 */
	protected $message_prefix = 'rest-v1:';

	/**
	 * Tribe__Tickets__REST__V1__Messages constructor.
	 *
	 * @since TBD
	 *
	 */
	public function __construct() {
		$this->messages = array(
			'missing-event-id'                 => __( 'The event ID is missing from the request', 'event-tickets' ),
			'event-not-found'                  => __( 'The requested post ID does not exist or is not an event', 'event-tickets' ),
			'event-no-venue'                   => __( 'The event does not have a venue assigned', 'event-tickets' ),
			'event-no-organizer'               => __( 'The event does not have an organizer assigned', 'event-tickets' ),
			'event-not-accessible'             => __( 'The requested event is not accessible', 'event-tickets' ),
		);
	}

	/**
	 * Returns the localized message associated with the slug.
	 *
	 * @since TBD
	 *
	 * @param string $message_slug
	 *
	 * @return string
	 */
	public function get_message( $message_slug ) {
		if ( isset( $this->messages[ $message_slug ] ) ) {
			return $this->messages[ $message_slug ];
		}

		return '';
	}

	/**
	 * Returns the associative array of all the messages handled by the class.
	 *
	 * @since TBD
	 *
	 * @return array An associative array in the `[ <slug> => <localized message> ]` format.
	 */
	public function get_messages() {
		return $this->messages;
	}

}
