<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use WP_REST_Server;

/**
 * Class REST
 *
 * @since   5.1.9
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class REST extends \tad_DI52_ServiceProvider {
	public function register() {
		$this->container->singleton( REST\On_Boarding_Endpoint::class );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since 5.1.6
	 */
	public function register_endpoints() {
		$this->container->make( REST\On_Boarding_Endpoint::class )->register();
	}
}
