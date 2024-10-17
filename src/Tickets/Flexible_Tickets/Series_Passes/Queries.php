<?php
/**
 * Handles the queries required by Series Passes to integrate with Events Tickets.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Tables\Series_Relationships;
use Tribe__Tickets__Query as Tickets_Query;
use Tribe__Events__Main as TEC;

/**
 * Class Queries.
 *
 * @since   5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
 */
class Queries {
	/**
	 * A reference to the Tickets Query handler.
	 *
	 * @since 5.8.0
	 *
	 * @var Tickets_Query
	 */
	private Tickets_Query $tickets_query;

	public function __construct( Tickets_Query $tickets_query ) {
		$this->tickets_query = $tickets_query;
	}

	/**
	 * Provides the sub-query to be used to restrict the list of Events to those that are ticketed, or not
	 * ticketed when the logic of the query is reversed.
	 *
	 * See the `tec_tickets_query_ticketed_status_subquery` filter in the Events Tickets plugin.
	 *
	 * @since 5.8.0
	 *
	 * @return string The sub-query to be used to restrict the list of Events to those that are ticketed, or not
	 *                ticketed when the logic of the query is reversed.
	 */
	public function filter_ticketed_status_query(): string {
		$meta_keys_in = $this->tickets_query->build_meta_keys_in();

		$series_relationships = Series_Relationships::table_name( true );

		/**
		 * This query fetches all Events AND Series that are ticketed by matching their post IDs to the `meta_value`
		 * of the ticket to post relationship meta keys.
		 * Then it conditionally looks at `post_type` of each result:
		 * - if an Event just return the post ID
		 * - if a Series, return the `event_post_id` for that series from the Series Relationships table.
		 * The `post_parent` is used to avoid picking up migrated child Events.
		 */

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT DISTINCT(IF(p.post_type = %s, p.ID, sr.event_post_id)) FROM $wpdb->posts p
			 JOIN $wpdb->postmeta pm ON ( $meta_keys_in ) AND p.ID = pm.meta_value
			 LEFT JOIN $series_relationships sr ON ( p.ID = sr.series_post_id )
				 WHERE p.post_type IN (%s,%s) AND (p.post_parent = 0 OR p.post_parent IS NULL)",
			TEC::POSTTYPE,
			TEC::POSTTYPE,
			Series_Post_Type::POSTTYPE,
		);

		return $query;
	}

	/**
	 * Provides the query that should be used to get the number of ticketed Events taking Events ticketed by
	 * proxy into account (i.e. Events that are ticketed because they are part of a Series that has Series Passes).
	 *
	 * @since 5.8.0
	 *
	 * @return string The query to use to get the number of ticketed Events.
	 */
	public function filter_ticketed_count_query(): string {
		$meta_keys_in = $this->tickets_query->build_meta_keys_in();

		$series_relationships = Series_Relationships::table_name( true );

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT(IF(p.post_type = %s, p.ID, sr.event_post_id))) FROM $wpdb->posts p
			 JOIN $wpdb->postmeta pm ON ( $meta_keys_in ) AND p.ID = pm.meta_value
			 LEFT JOIN $series_relationships sr ON ( p.ID = sr.series_post_id )
				WHERE p.post_type IN (%s,%s)
				AND (p.post_parent = 0 OR p.post_parent IS NULL)
				AND p.post_status NOT IN ('auto-draft', 'trash')",
			TEC::POSTTYPE,
			TEC::POSTTYPE,
			Series_Post_Type::POSTTYPE
		);

		return $query;
	}

	/**
	 * Provides the query that should be used to get the number of unticketed Events taking Events ticketed by
	 * proxy into account (i.e. Events that are ticketed because they are part of a Series that has Series Passes).
	 *
	 * @return string The query to use to get the number of unticketed Events.
	 */
	public function filter_unticketed_count_query(): string {
		$meta_keys_in = $this->tickets_query->build_meta_keys_in();

		$series_relationships = Series_Relationships::table_name( true );

		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT(p.ID)) from $wpdb->posts p WHERE p.ID NOT IN(
				 SELECT DISTINCT(IF(p.post_type = %s, p.ID, sr.event_post_id)) FROM $wpdb->posts p
							JOIN $wpdb->postmeta pm ON ( $meta_keys_in ) AND p.ID = pm.meta_value
							LEFT JOIN $series_relationships sr ON ( p.ID = sr.series_post_id )
								 WHERE p.post_type IN (%s,%s)
				 ) AND p.post_type = %s AND p.post_status NOT IN ('auto-draft', 'trash') AND (p.post_parent = 0 OR p.post_parent IS NULL)",
			TEC::POSTTYPE,
			TEC::POSTTYPE,
			Series_Post_Type::POSTTYPE,
			TEC::POSTTYPE
		);

		return $query;
	}
}
