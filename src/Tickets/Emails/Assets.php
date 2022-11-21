<?php
/**
 * Handles registering and setup for assets on Tickets Emails.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \tad_DI52_ServiceProvider;

/**
 * Class Assets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails
 */
class Assets extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

	}
}