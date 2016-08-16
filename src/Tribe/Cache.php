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
	protected static $keys = array();

	/**
	 * Resets all caches for the class.
	 */
	public static function reset_all() {
		foreach ( self::$keys as $key ) {
			wp_cache_delete( $key, 'Tribe__Tickets__Cache' );
		}
	}

	/**
	 * @return array An array of post IDs of posts that have no tickets assigned.
	 */
	public function posts_without_tickets() {

		return array();
	}
}