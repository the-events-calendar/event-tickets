<?php


class Tribe__Tickets__REST__V1__Post_Repository implements Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * A post type to get data request handler map.
	 *
	 * @var array
	 */
	protected $types_get_map = array();

	/**
	 * @var Tribe__REST__Messages_Interface
	 */
	protected $messages;

	public function __construct( Tribe__REST__Messages_Interface $messages = null ) {
		$this->types_get_map = array(
			tribe( 'tickets.rsvp' )->ticket_object => array( $this, 'get_ticket_data' ),
		);

		$this->messages = $messages ? $messages : tribe( 'tec.rest-v1.messages' );
	}

	/**
	 * Retrieves an array representation of the post.
	 *
	 * @param int    $id      The post ID.
	 * @param string $context Context of data.
	 *
	 * @return array An array representation of the post.
	 *
	 */
	public function get_data( $id, $context = '' ) {
		$post = get_post( $id );

		if ( empty( $post ) ) {
			return array();
		}

		if ( ! isset( $this->types_get_map[ $post->post_type ] ) ) {
			return (array) $post;
		}

		return call_user_func( $this->types_get_map[ $post->post_type ], $id, $context );
	}

	/**
	 * Returns an array representation of an event.
	 *
	 * @param int    $event_id An event post ID.
	 * @param string $context  Context of data.
	 *
	 * @return array|WP_Error Either the array representation of an event or an error object.
	 *
	 */
	public function get_ticket_data( $event_id, $context = '' ) {
		$event = get_post( $event_id );

		if ( empty( $event ) || ! tribe_is_event( $event ) ) {
			return new WP_Error( 'event-not-found', $this->messages->get_message( 'event-not-found' ) );
		}

		$meta = array_map( 'reset', get_post_custom( $event_id ) );

		$venue     = $this->get_venue_data( $event_id, $context );
		$organizer = $this->get_organizer_data( $event_id, $context );

		$data = array(
			'id'                     => $event_id,
			'global_id'              => false,
			'global_id_lineage'      => array(),
			'author'                 => $event->post_author,
			'status'                 => $event->post_status,
			'date'                   => $event->post_date,
			'date_utc'               => $event->post_date_gmt,
			'modified'               => $event->post_modified,
			'modified_utc'           => $event->post_modified_gmt,
			'status'                 => $event->post_status,
			'url'                    => get_the_permalink( $event_id ),
			'rest_url'               => tribe_tickets_rest_url( 'events/' . $event_id ),
			'title'                  => trim( apply_filters( 'the_title', $event->post_title ) ),
			'description'            => trim( apply_filters( 'the_content', $event->post_content ) ),
			'excerpt'                => trim( apply_filters( 'the_excerpt', $event->post_excerpt ) ),
			'timezone'               => isset( $meta['_EventTimezone'] ) ? $meta['_EventTimezone'] : '',
		);

		/**
		 * Filters the list of contexts that should trigger the attachment of the JSON LD information to the event
		 * REST representation.
		 *
		 * @since TBD
		 *
		 * @param array $json_ld_contexts An array of contexts.
		 */
		$json_ld_contexts = apply_filters( 'tribe_rest_event_json_ld_data_contexts', array( 'single' ) );

		if ( in_array( $context, $json_ld_contexts, true ) ) {
			$json_ld_data = tribe( 'tec.json-ld.event' )->get_data( $event );

			if ( $json_ld_data ) {
				$data['json_ld'] = $json_ld_data[ $event->ID ];
			}
		}

		/**
		 * Filters the data that will be returnedf for a single event.
		 *
		 * @param array   $data  The data that will be returned in the response.
		 * @param WP_Post $event The requested event.
		 */
		$data = apply_filters( 'tribe_rest_event_data', $data, $event );

		return $data;
	}

}