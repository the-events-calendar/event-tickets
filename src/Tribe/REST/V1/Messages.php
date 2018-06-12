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
			'missing-attendee-id'         => __( 'The attendee ID is missing from the request', 'event-tickets' ),
			'attendee-not-found'          => __( 'The requested post ID does not exist or is not an attendee', 'event-tickets' ),
			'attendee-not-accessible'     => __( 'The requested attendee is not accessible', 'event-tickets' ),
			'attendee-check-in-not-found' => __( 'The requested attendee check in is not available', 'event-tickets' ),
			'ticket-provider-not-found'   => __( 'The ticket provider for the requested ticket is not available', 'event-tickets' ),
			'ticket-post-not-found'       => __( 'The post associated with the requested ticket was not found', 'event-tickets' ),
			'ticket-object-not-found'     => __( 'The requested ticket object could not be built or found', 'event-tickets' ),
			'error-global-id-generation'  => __( 'The ticket global id could not be generated', 'event-tickets' ),
			'error-ticket-post'           => __( 'There was a problem while fetching the requested ticket post', 'event-tickets' )
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
