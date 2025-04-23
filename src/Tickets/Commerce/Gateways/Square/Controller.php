<?php
/**
 * Main file controlling Square integration.
 *
 * @since TBD
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Gateways\Square\Notices\Webhook_Notice;
use TEC\Tickets\Commerce\Gateways\Square\REST\On_Boarding_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\REST\Webhook_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\Webhooks;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Controller extends Controller_Contract {
	/**
	 * Register the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( Gateway::class );
		$this->container->singleton( Merchant::class );
		$this->container->singleton( WhoDat::class );
		$this->container->singleton( Order::class );
		$this->container->singleton( Settings::class );
		$this->container->singleton( On_Boarding_Endpoint::class );
		$this->container->singleton( Order_Endpoint::class );
		$this->container->singleton( REST\Webhook_Endpoint::class );

		$this->container->register( REST::class );
		$this->container->register( Assets::class );
		$this->container->register( Ajax::class );
		$this->container->register( Hooks::class );
		$this->container->register( Webhooks::class );
		$this->container->register( Notices\Controller::class );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( REST::class )->unregister();
		$this->container->get( Assets::class )->unregister();
		$this->container->get( Ajax::class )->unregister();
		$this->container->get( Hooks::class )->unregister();
		$this->container->get( Notices\Controller::class )->unregister();
	}
}
