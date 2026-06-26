<?php

/**
 * Class Tribe__Tickets__Query
 *
 * Modifies the query to allow ticket related filtering.
 */
class Tribe__Tickets__Query {

	/**
	 * @var string The slug of the query var used to filter posts by their ticketing status.
	 */
	public static $has_tickets = 'tribe-has-tickets';

	/**
	 *  Hooks to add query vars and filter the post query.
	 */
	public function hook() {
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_action( 'pre_get_posts', array( $this, 'restrict_by_ticketed_status' ) );
	}

	/**
	 * @param array $query_vars A list of allowed query variables.
	 *
	 * @return array $query_vars A list of allowed query variables.
	 *               plus ours.
	 */
	public function filter_query_vars( array $query_vars = array() ) {
		$query_vars[] = self::$has_tickets;

		return $query_vars;
	}

	/**
	 * Builds and returns the Closure that will be applied to the query `posts_where` filter to restrict
	 * posts by their ticketed status.
	 *
	 * @since 5.6.5
	 *
	 * @param WP_Query $query       The WP_Query instance.
	 * @param bool     $has_tickets Whether the posts should be restricted to those that have tickets or not.
	 *
	 * @return Closure The Closure that will be applied to the query `posts_where` filter to restrict posts by their
	 *                 ticketed status.
	 */
	protected function filter_by_ticketed_status( WP_Query $query, bool $has_tickets ): Closure {
		$filter = function ( $where, $this_query ) use ( &$filter, $query, $has_tickets ) {
			if ( ! ( $this_query === $query && is_string( $where ) ) ) {
				// Not the query we are looking for.
				return $where;
			}

			// Let's not run this filter again.
			remove_filter( 'posts_where', $filter );

			// Build the additional WHERE clause.
			$post_types = (array) $query->get( 'post_type', [ 'post' ] );

			global $wpdb;

			/**
			 * Filter the subquery used to filter posts by their ticketed status.
			 *
			 * @since 5.6.5
			 *
			 * @param string        $query       The subquery used to filter posts by their ticketed status as built by the default logic.
			 * @param bool          $has_tickets Whether the posts should be restricted to those that have tickets or not.
			 * @param array<string> $post_types  The post types the ticketed status filtering is being applied to.
			 */
			$query = apply_filters( 'tec_tickets_query_ticketed_status_subquery', null, $has_tickets, $post_types );

			if ( $query === null ) {
				// Build a complete list of meta keys to leverage the meta_key index; LIKE will not hit the index.
				$meta_keys_in = $this->build_meta_keys_in();

				$post_types_in = implode( "','", $post_types );

				/*
				 * A fast sub-query on the indexed `wp_postmeta.meta_key` column; then a slow comparison on few values
				 * in the `wp_postmeta.meta_value` column for a fast query.
				 */
				// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$query = "SELECT p.ID FROM $wpdb->posts p
    			JOIN $wpdb->postmeta pm ON ( $meta_keys_in ) AND pm.meta_value = CONCAT( p.ID, '' )
    			WHERE p.post_type IN ('$post_types_in')";
				// phpcs:enable
			}

			if ( $has_tickets ) {
				// Include only the posts that have tickets.
				$where .= " AND $wpdb->posts.ID IN ($query)";
			} else {
				// We need to exclude the posts that have tickets.
				$where .= " AND $wpdb->posts.ID NOT IN ($query)";
			}

			return $where;
		};

		return $filter;
	}

	/**
	 * If the `has-tickets` query var is set then limit posts by having
	 * or not having tickets assigned.
	 *
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function restrict_by_ticketed_status( WP_Query $query ) {
		$value = $query->get( self::$has_tickets, null );

		if ( $value === null ) {
			// The post type is not being filtered by its ticketed status at all.
			return;
		}

		$has_tickets = (bool) $value;

		add_filter( 'posts_where', $this->filter_by_ticketed_status( $query, $has_tickets ), 10, 2 );
	}

	/**
	 * Returns the number of ticketed posts of a certain type.
	 *
	 * @since 5.6.7
	 * @since 5.28.5 Resolve the `wp_posts` row via the primary key (`p.ID = pm.meta_value`) instead of `CONCAT( p.ID, '' )`, so the count no longer scans `wp_posts` proportionally to the post count.
	 *
	 * @param string $post_type The post type the ticketed count is being calculated for.
	 *
	 * @return int The number of ticketed posts of a certain type.
	 */
	public function get_ticketed_count( string $post_type ): int {
		/**
		 * Filters the query used to get the number of ticketed posts of a certain type.
		 *
		 * @since 5.6.5
		 *
		 * @param string|null $query     The query used to get the number of ticketed posts of a certain type.
		 *                               If null, the default query will be used.
		 * @param string      $post_type The post type the ticketed count is being calculated for.
		 */
		$query = apply_filters( 'tec_tickets_query_ticketed_count_query', null, $post_type );

		global $wpdb;

		if ( $query === null ) {
			// Build a complete list of meta keys to leverage the meta_key index; LIKE will not hit the index.
			$meta_keys_in = $this->build_meta_keys_in();

			/*
			 * Drive the query from the indexed `wp_postmeta.meta_key` column: the ticket-to-event meta set is small
			 * (one row per ticket), so resolving each to its event via the `wp_posts` primary key (`p.ID = pm.meta_value`)
			 * is fast. The previous `pm.meta_value = CONCAT( p.ID, '' )` form wrapped the indexed `p.ID`, forcing a
			 * full scan of `wp_posts` and making this query slow on sites with many posts.
			 */
			// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$query = $wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID) FROM $wpdb->postmeta pm
				  INNER JOIN $wpdb->posts p ON p.ID = pm.meta_value
				  WHERE ( $meta_keys_in ) AND p.post_type = %s AND p.post_status NOT IN ('auto-draft', 'trash')",
				$post_type
			);
			// phpcs:enable

			// $query is built via $wpdb->prepare() above.
			return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// $query comes from the filter above; preparing it is the filter's responsibility.
		return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Returns the number of unticketed posts of a certain type.
	 *
	 * @since 5.6.7
	 * @since 5.28.5 Derive the count as `total - ticketed` instead of a `NOT IN ( <ticketed subquery> )`, so it resolves with two indexed queries instead of scanning `wp_posts`.
	 *
	 * @param string $post_type The post type the unticketed count is being calculated for.
	 *
	 * @return int The number of unticketed posts of a certain type.
	 */
	public function get_unticketed_count( string $post_type ): int {
		/**
		 * Filters the query used to get the number of unticketed posts of a certain type.
		 *
		 * @since 5.6.5
		 *
		 * @param string|null $query     The query used to get the number of unticketed posts of a certain type.
		 *                               If null, the default query will be used.
		 * @param string      $post_type The post type the unticketed count is being calculated for.
		 */
		$query = apply_filters( 'tec_tickets_query_unticketed_count_query', null, $post_type );

		global $wpdb;

		if ( $query === null ) {
			/*
			 * Unticketed is derived as `total - ticketed` rather than a `NOT IN ( <ticketed subquery> )`.
			 * `NOT IN` against an unindexed sub-query of post IDs scales with the number of posts and could take
			 * tens of seconds on large sites. The total is a fast `COUNT(*)` served by the `type_status_date`
			 * index, and `get_ticketed_count()` is a single indexed query, so this resolves with two fast,
			 * indexed queries. The `post_status` filter matches `get_ticketed_count()` so the subtraction stays
			 * consistent.
			 */
			$total = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status NOT IN ('auto-draft', 'trash')",
					$post_type
				)
			);

			return max( 0, $total - $this->get_ticketed_count( $post_type ) );
		}

		// $query comes from the filter above; preparing it is the filter's responsibility.
		return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Builds the query part based on `meta_key`s to "unroll" it into compiled values.
	 *
	 * Why? The `wp_postmeta.meta_key` column is indexed. Running a LIKE query will not use the index
	 * and will generate a slow query, using complete keys will be fast as it's a byte comparison.
	 *
	 * @since 5.6.5
	 *
	 * @return string The query part based on `meta_key`s to "unroll" it into compiled values.
	 */
	public function build_meta_keys_in(): string {
		/** @var class-string $class */
		foreach ( Tribe__Tickets__Tickets::modules() as $class => $module ) {
			$instance    = $class::get_instance();
			$meta_keys[] = $instance->get_event_key();
		}

		global $wpdb;

		return implode(
			' OR ',
			array_map( fn( $meta_key ) => $wpdb->prepare( 'pm.meta_key = %s', $meta_key ), $meta_keys )
		);
	}
}
