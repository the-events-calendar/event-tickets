<?php


/**
 * Class Tribe__Ticket__Cache
 *
 * Stores and return costly site-wide information.
 */
class Tribe__Tickets__Cache {

	/**
	 * @var array
	 */
	protected static $keys = array(
		'posts_with_tickets',
		'posts_without_tickets',
	);

	/**
	 * @var int The expiration time in seconds.
	 */
	protected $expiration = 60;

	/**
	 * Resets all caches for the class.
	 */
	public static function reset_all() {
		foreach ( self::$keys as $key ) {
			delete_transient( __CLASS__ . $key );
		}
	}

	/**
	 * @return array An array of post IDs of posts that have no tickets assigned.
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
}