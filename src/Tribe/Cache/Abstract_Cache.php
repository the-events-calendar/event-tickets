<?php


/**
 * Class Tribe__Tickets__Cache__Abstract_Cache
 *
 * Implements methods common to all caches implementations.
 */
abstract class Tribe__Tickets__Cache__Abstract_Cache {

	/**
	 * @var array
	 */
	protected $keys = array(
		'posts_with_tickets',
		'posts_without_tickets',
	);
	/**
	 * @var int The expiration time in seconds.
	 */
	protected $expiration = 60;

	/**
	 * Sets the expiration time for the cache.
	 *
	 * @param int $seconds
	 *
	 * @return void
	 */
	public function set_expiration_time( $seconds ) {
		$this->expiration = $seconds;
	}

	/**
	 * @return array
	 */
	protected function fetch_posts_with_tickets() {
		$supported_types = tribe_get_option( 'ticket-enabled-post-types', array() );

		if ( empty( $supported_types ) ) {
			$ids = array();
		}

		/** @var \wpdb $wpdb */
		global $wpdb;

		$post_types = "('" . implode( "','", $supported_types ) . "')";

		$query = "SELECT DISTINCT(pm.meta_value) FROM {$wpdb->postmeta} pm
				LEFT JOIN {$wpdb->posts} p
				ON pm.meta_value = p.ID
				WHERE p.post_type IN {$post_types}
				AND pm.meta_key LIKE '_tribe_%_for_event'
				AND pm.meta_value IS NOT NULL";
		$ids   = $wpdb->get_col( $query );

		$ids = is_array( $ids ) ? $ids : array();

		return $ids;
	}

	/**
	 * @return array
	 */
	protected function fetch_posts_without_tickets() {
		$supported_types = tribe_get_option( 'ticket-enabled-post-types', array() );

		if ( empty( $supported_types ) ) {
			$ids = array();
		}

		/** @var \wpdb $wpdb */
		global $wpdb;

		$post_types = "('" . implode( "','", $supported_types ) . "')";

		$query = "SELECT DISTINCT(ID) FROM {$wpdb->posts}
				WHERE post_type IN {$post_types}";

		$posts_with_tickets = $this->posts_with_tickets();

		if ( ! empty( $posts_with_tickets ) ) {
			$excluded = '(' . implode( ',', $posts_with_tickets ) . ')';
			$query .= " AND ID NOT IN {$excluded}";
		}
		$ids = $wpdb->get_col( $query );

		$ids = is_array( $ids ) ? $ids : array();

		return $ids;
	}
}