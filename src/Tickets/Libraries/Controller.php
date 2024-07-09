<?php
/**
 * The Controller to set up libraries.
 *
 * @since   TBD
 * @package TEC\Tickets\Libraries
 */

namespace TEC\Tickets\Libraries;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Controller for setting up libraries.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Libraries
 */
class Controller extends Controller_Contract {
	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		tribe_register_provider( Uplink\Controller::class );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
	}
}
