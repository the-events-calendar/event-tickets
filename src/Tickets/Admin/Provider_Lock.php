<?php
/**
 * Decides whether an event's ticket provider can still be changed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

use TEC\Tickets\RSVP\V2\Constants;
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
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post the tickets are attached to.
	 *
	 * @return bool
	 */
	public function is_locked( int $post_id ): bool {
		foreach ( Tickets::get_all_event_tickets( $post_id ) as $ticket ) {
			if ( $ticket->provider_class === Legacy_RSVP::class || $ticket->type() === Constants::TC_RSVP_TYPE ) {
				continue;
			}

			return true;
		}

		return false;
	}
}
