<?php
/**
 * Manages the compatibility of Event Tickets with the new Recurrence Back-end Engine in Events Calendar Pro.
 *
 * @since   5.5.0
 *
 * @package TEC\Tickets\Recurrence
 */

namespace TEC\Tickets\Recurrence;

use TEC\Events_Pro\Custom_Tables\V1\Models\Event;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;

/**
 * Custom Tables Compatibility for Tickets
 *
 * @since   5.5.0
 *
 * @package TEC\Events_Pro\Custom_Tables\V1\Tickets
 */
class Compatibility {

	/**
	 * TEC post types that cannot have tickets attached.
	 *
	 * @since 5.5.0
	 *
	 * @var array
	 */
	protected static $restricted_post_types = [ Series::POSTTYPE ];

	/**
	 * Get a list of post types to restrict adding tickets to.
	 *
	 * @since 5.5.0
	 *
	 * @return array
	 */
	public static function get_restricted_post_types() {
		return static::$restricted_post_types;
	}

	/**
	 * Checks if a WP post object is allowed to have tickets.
	 *
	 * @since 5.5.0
	 *
	 * @param \WP_Post $post the object to check.
	 *
	 * @return bool
	 */
	public static function object_can_have_tickets( \WP_Post $post ) {

		if ( 'tribe_events' !== $post->post_type ) {
			return true;
		}

		if ( Event::is_part_of_series( $post->ID ) ) {
			return false;
		}

		return true;
	}

}