<?php
/**
 * Handles registering and setup for assets on Tickets Emails.
 *
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails;

use \tad_DI52_ServiceProvider;

/**
 * Class Assets.
 *
 * @since 5.5.6
 *
 * @package TEC\Tickets\Emails
 */
class Assets extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.5.6
	 */
	public function register() {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

	}
}