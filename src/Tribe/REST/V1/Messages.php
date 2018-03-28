<?php


class Tribe__Tickets__REST__V1__Messages implements Tribe__REST__Messages_Interface {

	/**
	 * @var string
	 */
	protected $message_prefix = 'rest-v1:';

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
	 * @return array An associative array in the `[ <slug> => <localized message> ]` format.
	 */
	public function get_messages() {
		return $this->messages;
	}

	/**
	 * Prefixes a message slug with a common root.
	 *
	 * Used to uniform the slug format to the one used by the `Tribe__Tickets__Aggregator__Service` class.
	 *
	 * @see Tribe__Tickets__Aggregator__Service::register_messages()
	 *
	 * @param string $message_slug
	 *
	 * @return string The prefixed message slug.
	 */
	public function prefix_message_slug( $message_slug ) {
		return $this->message_prefix . $message_slug;
	}
}
