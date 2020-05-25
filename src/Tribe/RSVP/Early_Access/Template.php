<?php

namespace Tribe\Tickets\RSVP\Early_Access;

/**
 * Class Early_Access
 *
 * Handles Early Access for the new RSVP template.
 *
 * @since TBD
 *
 * @package Tribe\Tickets\RSVP
 */
class Template {

	/**
	 * Changes the RSVP template if in Early Access
	 *
	 * @param string $file The template file being filtered.
	 *
	 * @since TBD
	 *
	 * @filter tribe_events_tickets_template_tickets/rsvp 10 1
	 * @see \Tribe\Tickets\RSVP\Service_Provider::register_early_access
	 * @see \Tribe__Tickets__Tickets::getTemplateHierarchy
	 *
	 * @return string
	 */
	public function override_template( $file ) {
		$file = str_replace( 'rsvp.php', 'rsvp-early-access.php', $file );

		return $file;
	}
}