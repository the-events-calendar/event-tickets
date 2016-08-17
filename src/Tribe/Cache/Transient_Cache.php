<?php


/**
 * Class Tribe__Ticket__Cache__Transient_Cache
 *
 * Stores and return costly site-wide information.
 */
class Tribe__Tickets__Cache__Transient_Cache implements Tribe__Tickets__Cache__Cache_Interface {

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
	 * Resets all caches.
	 */

	public function reset_all() {
		foreach ( $this->keys as $key ) {
			delete_transient( __CLASS__ . $key );
		}
	}

	/**
	 * Returns array of post IDs of posts that have no tickets assigned.
	 *
	 * Please note that the list is aware of supported types.
	 *
	 * @return array
	 */
	public function posts_without_tickets() {
		$ids = get_transient( __CLASS__ . __METHOD__ );

		if ( false === $ids ) {
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

			set_transient( __CLASS__ . __METHOD__, $ids, $this->expiration );
		}

		return $ids;
	}

	/**
	 * Returns array of post IDs of posts that have at least one ticket assigned.
	 *
	 * Please note that the list is aware of supported types.
	 *
	 * @return array
	 */
	public function posts_with_tickets() {
		$ids = get_transient( __CLASS__ . __METHOD__ );

		if ( false === $ids ) {
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

			set_transient( __CLASS__ . __METHOD__, $ids, $this->expiration );
		}

		return $ids;
	}

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
}