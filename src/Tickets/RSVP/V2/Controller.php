<?php
/**
 * V2 RSVP Controller - TC-based implementation.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\RSVP\RSVP_Controller_Methods;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Controller extends Controller_Contract {
	use RSVP_Controller_Methods;

	/**
	 * Store hook callbacks for clean unregistration.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected array $hooks = [];

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->singleton( Constants::class );

		// Register assets.
		$this->container->register( Assets::class );

		$this->register_common_rsvp_implementations();

		/**
		 * Fires after the RSVP V2 controller has been registered.
		 *
		 * This action allows other plugins (e.g., ET+) to register their
		 * own V2 RSVP components after the core V2 infrastructure is ready.
		 *
		 * @since TBD
		 */
		do_action( 'tec_tickets_rsvp_v2_registered' );
	}
}
