<?php
/**
 * Is RSVP trait.
 *
 * @since TBD
 */

namespace TEC\Tickets\Commerce\Traits;

use TEC\Tickets\Commerce\RSVP\Constants;

/**
 * Trait Is_RSVP
 *
 * @since TBD
 */
trait Is_RSVP {

	/**
	 * Determine if a thing is a RSVP.
	 *
	 * This looks to see whether the array of data has the "type" key set to
	 * "tc-rsvp". If the type key is not set, or if it is set to something other
	 * than "tc-rsvp", this will return false.
	 *
	 * @since TBD
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a tc-rsvp.
	 */
	protected function is_rsvp( array $thing ): bool {
		// If there's no type key, then assume it's a ticket.
		if ( ! array_key_exists( 'type', $thing ) ) {
			return true;
		}

		return Constants::TC_RSVP_TYPE === $thing['type'];
	}
}
