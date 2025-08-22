<?php
/**
 * REST TEC V1 Controller for Event Tickets.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * REST TEC V1 Controller for Event Tickets.
 *
 * @since 5.26.0
 *
 * @package TEC\Tickets\REST\TEC\V1
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the controller.
	 *
	 * @since 5.26.0
	 */
	public function do_register(): void {
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return;
		}

		$this->container->register( Endpoints::class );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since 5.26.0
	 */
	public function unregister(): void {
		if ( $this->container->isBound( Endpoints::class ) ) {
			$this->container->get( Endpoints::class )->unregister();
		}
	}
}
