<?php
/**
 * Manages the compatibility of Event Tickets with the new Recurrence Back-end Engine in Events Calendar Pro.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Recurrence
 */

namespace TEC\Tickets\Recurrence;

use TEC\Events_Pro\Custom_Tables\V1\Models\Event;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;

/**
 * Custom Tables Compatibility for Tickets
 *
 * @since   TBD
 *
 * @package TEC\Events_Pro\Custom_Tables\V1\Tickets
 */
class Compatibility {

	/**
	 * TEC post types that cannot have tickets attached.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static $restricted_post_types = [ Series::POSTTYPE ];

	/**
	 * Get a list of post types to restrict adding tickets to.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_restricted_post_types() {
		return static::$restricted_post_types;
	}

	/**
	 * Checks if a WP post object is allowed to have tickets.
	 *
	 * @since TBD
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