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
	public function posts_without_tickets();

	/**
	 * Returns array of post IDs of posts that have at least one ticket assigned.
	 *
	 * Please note that the list is aware of supported types.
	 *
	 * @return array
	 */
	public function posts_with_tickets();
}