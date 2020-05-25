<?php

namespace Tribe\Tickets\RSVP\Early_Access;


/**
 * Class Early_Access
 *
 * Handles Early Access for the new RSVP template.
 *
 * @since TBD
 *
 * @package Tribe\Tickets\RSVP\Early_Access
 */
class Early_Access {

	/**
	 * Returns whether we are using RSVP Early Access or not.
	 *
	 * @since TBD
	 *
	 * @return bool Whether it's early access or not.
	 */
	public function is_rsvp_early_access() {
		$is_early_access = defined( 'TRIBE_TICKETS_RSVP_EARLY_ACCESS' ) && tribe_is_truthy( TRIBE_TICKETS_RSVP_EARLY_ACCESS );

		return (bool) apply_filters( 'tribe_tickets_is_rsvp_early_access', $is_early_access );
	}
}