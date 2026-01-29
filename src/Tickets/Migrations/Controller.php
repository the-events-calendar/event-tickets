<?php
/**
 * Main Migrations Controller.
 *
 * @since TBD
 */

namespace TEC\Tickets\Migrations;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use function TEC\Common\StellarWP\Migrations\migrations;

/**
 * Main controller for Migrations functionality.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		migrations()->get_registry()->register( 'rsvp-to-tc', RSVP_To_Tickets_Commerce::class );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		migrations()->get_registry()->offsetUnset( 'rsvp-to-tc' );
	}
}
