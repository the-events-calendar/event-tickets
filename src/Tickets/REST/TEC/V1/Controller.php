<?php
/**
 * REST TEC V1 Controller for Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * REST TEC V1 Controller for Event Tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\REST\TEC\V1
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->container->register( Endpoints::class );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->container->get( Endpoints::class )->unregister();
	}
}
