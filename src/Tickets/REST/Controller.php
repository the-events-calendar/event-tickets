<?php
/**
 * Controller for the Tickets REST API.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

use TEC\Tickets\REST\TEC\V1\Controller as V1_Controller;

/**
 * Controller for the Tickets REST API.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.26.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->register( V1_Controller::class );
	}

	/**
	 * Unregisters the filters and actions hooks added by the controller.
	 *
	 * @since 5.26.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( V1_Controller::class )->unregister();
	}
}
