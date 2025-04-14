<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Gateways\Square\REST\On_Boarding_Endpoint;
use TEC\Tickets\Commerce\Gateways\Square\REST\Order_Endpoint;
use TEC\Common\Contracts\Service_Provider;

/**
 * Class REST
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class REST extends Service_Provider {
	/**
	 * Registers the REST API endpoints for Square.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->container->make( On_Boarding_Endpoint::class )->register();
		$this->container->make( Order_Endpoint::class )->register();
	}

	/**
	 * Register the REST API endpoint classes in the container.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( On_Boarding_Endpoint::class );
		$this->container->singleton( Order_Endpoint::class );
	}
}
