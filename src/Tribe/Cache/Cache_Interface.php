<?php


/**
 * Class Tribe__Ticket__Cache__Transient_Cache
 *
 * Stores and return costly site-wide information.
 */
interface Tribe__Tickets__Cache__Cache_Interface {

	/**
	 * Resets all caches.
	 */
	public function reset_all();

	/**
	 * Returns array of post IDs of posts that have no tickets assigned.
	 *
	 * Please note that the list is aware of supported types.
	 *
	 * @return array
	 */
	public function posts_without_ticket_types();

	/**
	 * Returns array of post IDs of posts that have at least one ticket assigned.
	 *
	 * Please note that the list is aware of supported types.
	 *
	 * @return array
	 */
	public function posts_with_ticket_types();

	/**
	 * Returns an array of all past events post IDs.
	 *
	 * @return array
	 */
	public function past_events();

	/**
	 * Sets the expiration time for the cache.
	 *
	 * @param int $seconds
	 *
	 * @return void
	 */
	public function set_expiration_time( $seconds );
}