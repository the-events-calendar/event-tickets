<?php
/**
 * Is RSVP trait.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V2\Traits;

use TEC\Tickets\RSVP\V2\Constants;

/**
 * Trait Is_RSVP
 *
 * @since TBD
 */
trait Is_RSVP {
	/**
	 * Determine if a thing is an RSVP.
	 *
	 * @since TBD
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a tc-rsvp.
	 */
	protected function is_rsvp( array $thing ): bool {
		return isset( $thing['type'] ) && $thing['type'] === Constants::TC_RSVP_TYPE;
	}
}
