<?php

namespace TEC\Tickets;

/**
 * Class Event
 *
 * @since 5.1.9
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
	 * @since 5.8.2 Add the `$context` parameter.
	 *
	 * @param numeric $event_id The event ID to be normalized.
	 * @param string $context The context in which the event ID is being filtered.
	 *
	 * @return int|null The filtered value.
	 */
	public static function filter_event_id( $event_id, $context = 'default' ): ?int {
		$original_id = $event_id;

		/**
		 * This filter allows retrieval of an event ID to be filtered before being accessed elsewhere.
		 *
		 * @since 5.5.6
		 * @since 5.8.2 Add the `$context` parameter and the `$original_id` parameter.
		 *
		 * @param int|null $event_id   The event ID to be filtered.
		 * @param string   $context    The context in which the event ID is being filtered.
		 * @param int      $original_id The original event ID.
		 */
		$event_id = apply_filters( 'tec_tickets_filter_event_id', $event_id, $context, $original_id );

		return is_numeric( $event_id ) ? (int) $event_id : null;
	}
}
