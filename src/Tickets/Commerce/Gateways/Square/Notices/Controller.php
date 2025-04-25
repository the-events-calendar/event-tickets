<?php
/**
 * Square Notices Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Notices
 */

namespace TEC\Tickets\Commerce\Gateways\Square\Notices;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square\Notices
 */
class Controller extends Controller_Contract {

	/**
	 * Register the notice providers.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		// Register all notice classes as singletons.
		$this->container->singleton( Webhook_Notice::class );

		// Register the notice instances.
		$this->container->get( Webhook_Notice::class )->register();
	}

	/**
	 * Unregisters the notice providers.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
	}
}
