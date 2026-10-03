<?php
/**
 * Decides whether an event's ticket provider can still be changed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

use Tribe__Tickets__RSVP as Legacy_RSVP;
use Tribe__Tickets__Tickets as Tickets;

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
	 * Only ticket IDs are queried, never hydrated ticket objects, and the first provider with a ticket wins.
	 * Tickets Commerce RSVPs are left out by the RSVP V2 repository filter on admin queries.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post the tickets are attached to.
	 *
	 * @return bool
	 */
	public function is_locked( int $post_id ): bool {
		foreach ( array_keys( Tickets::modules() ) as $provider_class ) {
			if ( Legacy_RSVP::class === $provider_class ) {
				continue;
			}

			if ( $provider_class::get_instance()->get_tickets_ids( $post_id ) ) {
				return true;
			}
		}

		return false;
	}
}
