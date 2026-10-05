<?php
/**
 * Decides whether an event's ticket provider can still be changed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

use TEC\Tickets\RSVP\V2\Constants as RSVP_V2_Constants;

/**
 * Class Provider_Lock.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */
final class Provider_Lock {

	/**
	 * Whether the ticket provider of a post is locked.
	 *
	 * The provider is locked once the post has a ticket; RSVPs, both legacy and Tickets Commerce, do not count.
	 *
	 * A single ticket ID is queried, never hydrated ticket objects, since one ticket is enough to lock.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post the tickets are attached to.
	 *
	 * @return bool
	 */
	public function is_locked( int $post_id ): bool {
		$ticket_post_types = array_values(
			array_diff_key( tribe_tickets()->ticket_types(), [ 'rsvp' => true ] )
		);

		return (bool) tribe_tickets()
			->where( 'event', $post_id )
			->where( 'post_type', $ticket_post_types )
			->where( 'meta_not_equals', '_type', RSVP_V2_Constants::TC_RSVP_TYPE )
			->per_page( 1 )
			->get_ids();
	}
}
