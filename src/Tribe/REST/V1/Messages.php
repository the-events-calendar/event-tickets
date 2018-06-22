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
			'missing-ticket-id'         => __( 'The ticket ID is missing from the request', 'event-tickets' ),
			'ticket-not-found'          => __( 'The requested post ID does not exist or is not a ticket', 'event-tickets' ),
			'ticket-not-accessible'     => __( 'The requested ticket is not accessible', 'event-tickets' ),
			'ticket-check-in-not-found' => __( 'The requested ticket check in is not available', 'event-tickets' ),
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

	/**
	 * Prefixes a message slug with a common root.
	 *
	 * @since TBD
	 *
	 * Used to uniform the slug format to the one used by the `Tribe__Events__Aggregator__Service` class.
	 *
	 * @see Tribe__Events__Aggregator__Service::register_messages()
	 *
	 * @param string $message_slug
	 *
	 * @return string The prefixed message slug.
	 */
	public function prefix_message_slug( $message_slug ) {
		return $this->message_prefix . $message_slug;
	}

}
