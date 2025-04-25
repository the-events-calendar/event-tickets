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
		$this->container->register( Webhook_Notice::class );
	}

	/**
	 * Unregisters the notice providers.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->unregister( Webhook_Notice::class );
	}
}
