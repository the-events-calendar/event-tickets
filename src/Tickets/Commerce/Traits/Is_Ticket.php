<?php
/**
 * Is Ticket trait.
 *
 * @since 5.18.0
 */

namespace TEC\Tickets\Commerce\Traits;

/**
 * Trait Is_Ticket
 *
 * @since 5.18.0
 */
trait Is_Ticket {

	/**
	 * Determine if a thing is a ticket.
	 *
	 * This looks to see whether the array of data has the "type" key set to
	 * "ticket". If the type key is not set, or if it is set to something other
	 * than "ticket", this will return false.
	 *
	 * @since 5.18.0
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a ticket.
	 */
	protected function is_ticket( array $thing ): bool {
		// If there's no type key, then assume it's a ticket.
		if ( ! array_key_exists( 'type', $thing ) ) {
			return true;
		}

		return 'ticket' === $thing['type'];
	}
}
