<?php
/**
 * RSVP V2 Controller placeholder.
 *
 * @since TBD
 */

namespace TEC\Tickets\RSVP\V2;

use RuntimeException;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Placeholder controller for future RSVP V2 implementation.
 *
 * @since TBD
 */
class Controller extends Controller_Contract {

	/**
	 * Registers the controller - throws exception as V2 is not implemented.
	 *
	 * @since TBD
	 *
	 * @throws RuntimeException Always throws because V2 is not implemented.
	 *
	 * @return void
	 */
	protected function do_register(): void {
		throw new RuntimeException(
			'RSVP V2 is not implemented yet. Use V1 controller.'
		);
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// Nothing to unregister - V2 is not implemented.
	}
}
