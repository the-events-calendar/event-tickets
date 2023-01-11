<?php

namespace TEC\Tickets;

/**
 * Class Event
 *
 * @since   5.1.9
 *
 * @package TEC\Tickets
 */
class Event {

	/**
	 * Value stored for the Events from TEC.
	 *
	 * @since 5.1.9
	 *
	 * @var string
	 */
	protected static $post_type = 'tribe_events';

	/**
	 * Gets the TEC events CPT, will fallback into the Static variable on this class, but will try to pull from
	 * TEC main class constant first.
	 *
	 * @since 5.1.9
	 *
	 *
	 * @return string
	 */
	public static function get_post_type() {
		if ( class_exists( '\Tribe__Events__Main' ) ) {
			return \Tribe__Events__Main::POSTTYPE;
		}
		return static::$post_type;
	}

	/**
	 * Will filter event ID input to a filtered and relevant ID.
	 *
	 * @since 5.5.6
	 *
	 * @param numeric $event_id The event ID to be normalized.
	 *
	 * @return int|null The filtered value.
	 */
	public static function filter_event_id( $event_id ): ?int {
		/**
		 * This filter allows retrieval of an event ID to be filtered before being accessed elsewhere.
		 *
		 * @since 5.5.6
		 *
		 * @param int|null The event ID to be filtered.
		 */
		$event_id = apply_filters( 'tec_tickets_filter_event_id', $event_id );

		return is_numeric( $event_id ) ? (int) $event_id : null;
	}
}