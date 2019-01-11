<?php

/**
 * Initialize Gutenberg Rest Compatibility layers for WP api and Tickets
 *
 * @todo  Remove this on class when we move into using our own API for RSVP
 *
 * @since 4.9
 */
class Tribe__Tickets__Editor__REST__Compatibility {
	/**
	 * Register the required Rest filters fields for good Gutenberg saving
	 *
	 * @since 4.9
	 *
	 * @return boolean
	 */
	public function hook() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}
		add_filter( 'get_post_metadata', array( $this, 'filter_going_fields' ), 15, 4 );
		add_filter( 'get_post_metadata', array( $this, 'filter_has_attendee_info_fields' ), 15, 4 );
		add_filter( 'updated_post_meta', array( $this, 'trigger_update_capacity' ), 15, 4 );

		return true;
	}

	/**
	 * When updating the Value of capacity for a RSVP we update Stock and some other Meta values
	 *
	 * @since 4.9
	 *
	 * @param  int    $meta_id
	 * @param  int    $object_id
	 * @param  string $meta_key
	 * @param  mixed  $capacity
	 *
	 * @return null
	 */
	public function trigger_update_capacity( $meta_id, $object_id, $meta_key, $capacity ) {
		// Bail if not capacity
		if ( tribe( 'tickets.handler' )->key_capacity !== $meta_key ) {
			return;
		}

		$object = get_post( $object_id );

		// Bail on wrong post type
		if ( tribe( 'tickets.rsvp' )->ticket_object !== $object->post_type ) {
			return;
		}

		// Fetch capacity field, if we don't have it use default (defined above)
		$capacity = trim( $capacity );
		$stock    = absint( get_post_meta( $object_id, '_stock', true ) );

		// If empty we need to modify to the default
		if ( '' === $capacity ) {
			$capacity = -1;
		}

		// The only available value lower than zero is -1 which is unlimited
		if ( 0 > $capacity ) {
			$capacity = -1;
		}

		if ( -1 !== $capacity ) {
			$totals = tribe( 'tickets.handler' )->get_ticket_totals( $object_id );
			$stock  -= $totals['pending'] + $totals['sold'];

			update_post_meta( $object_id, '_manage_stock', 'yes' );
			update_post_meta( $object_id, '_stock', $stock );
		} else {
			// unlimited stock
			delete_post_meta( $object_id, '_stock_status' );
			update_post_meta( $object_id, '_manage_stock', 'no' );
			delete_post_meta( $object_id, '_stock' );
			delete_post_meta( $object_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE );
			delete_post_meta( $object_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP );
		}
	}

	/**
	 * Populates Going and Not going fields for the Rest API data Endpoint in WordPress
	 *
	 * @since 4.9
	 *
	 * @param  mixed  $check
	 * @param  int    $object_id
	 * @param  string $meta_key
	 * @param  bool   $single
	 *
	 * @return null|int
	 */
	public function filter_going_fields( $check, $object_id, $meta_key, $single ) {

		$valid_keys = array(
			'_tribe_ticket_going_count',
			'_tribe_ticket_not_going_count',
		);

		if ( ! in_array( $meta_key, $valid_keys ) ) {
			return $check;
		}

		$repository    = tribe( 'tickets.rest-v1.repository' );
		$ticket_object = tribe_tickets_get_ticket_provider( $object_id );

		if ( ! $ticket_object instanceof Tribe__Tickets__RSVP ) {
			return $check;
		}

		$attendees = $repository->get_ticket_attendees( $object_id );
		if ( false === $attendees ) {
			return $check;
		}

		$going     = 0;
		$not_going = 0;

		foreach ( $attendees as $attendee ) {
			if ( isset( $attendee['rsvp_going'] ) && true === $attendee['rsvp_going'] ) {
				$going++;
			} else {
				$not_going++;
			}
		}

		if ( $valid_keys[0] === $meta_key ) {
			return (string) $going;
		}

		if ( $valid_keys[1] === $meta_key ) {
			return (string) $not_going;
		}
	}

		/**
	 * Populates has attendee info fields field for the Rest API data Endpoint in WordPress
	 *
	 * @since 4.9
	 *
	 * @param  mixed  $check
	 * @param  int    $object_id
	 * @param  string $meta_key
	 * @param  bool   $single
	 *
	 * @return null|bool
	 */
	public function filter_has_attendee_info_fields( $check, $object_id, $meta_key, $single ) {

		$valid_keys = array(
			'_tribe_ticket_has_attendee_info_fields',
		);

		if ( ! in_array( $meta_key, $valid_keys ) ) {
			return $check;
		}

		$ticket_object = tribe_tickets_get_ticket_provider( $object_id );

		if ( ! $ticket_object instanceof Tribe__Tickets__RSVP ) {
			return $check;
		}

		return tribe( 'tickets.data_api' )->ticket_has_meta_fields( $object_id );
	}
}
