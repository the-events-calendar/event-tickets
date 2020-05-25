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
	 * Name of option that holds whether to use RSVP Early Access.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $option_rsvp_early_access = 'tribe_tickets_rsvp_early_access';

	/**
	 * Returns whether we are using RSVP Early Access or not.
	 *
	 * @since TBD
	 *
	 * @return bool Whether it's early access or not.
	 */
	public function is_rsvp_early_access() {
		return (bool) get_option( self::$option_rsvp_early_access, false );
	}

	/**
	 * Set the option to whether use RSVP Early Access or not.
	 *
	 * @since TBD
	 *
	 * @param bool $is_early_access
	 *
	 * @return bool Whether the update was successful or not.
	 */
	public function set_rsvp_early_access( bool $is_early_access ) {
		return update_option( self::$option_rsvp_early_access, $is_early_access );
	}
}