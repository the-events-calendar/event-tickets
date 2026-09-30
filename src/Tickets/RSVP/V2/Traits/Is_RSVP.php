<?php
/**
 * Is RSVP trait.
 *
 * @since 5.30.0
 */

namespace TEC\Tickets\RSVP\V2\Traits;

use TEC\Tickets\RSVP\V2\Constants;

/**
 * Trait Is_RSVP
 *
 * @since 5.30.0
 */
trait Is_RSVP {
	/**
	 * Determine if a thing is an RSVP.
	 *
	 * @since 5.30.0
	 *
	 * @param array $thing The thing to check.
	 *
	 * @return bool Whether the thing is a tc-rsvp.
	 */
	protected function is_rsvp( array $thing ): bool {
		return isset( $thing['type'] ) && $thing['type'] === Constants::TC_RSVP_TYPE;
	}
}
