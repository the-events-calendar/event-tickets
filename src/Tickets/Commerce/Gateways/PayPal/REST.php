<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use WP_REST_Server;

/**
 * Class REST
 *
 * @since 5.1.9
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class REST extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Registers the endpoint singletons.
	 *
	 * @since TBD Registers the webhook endpoint.
	 */
	public function register() {
		$this->container->singleton( REST\Webhook_Endpoint::class );
		$this->container->singleton( REST\On_Boarding_Endpoint::class );
		$this->container->singleton( REST\Order_Endpoint::class );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since 5.1.6
	 * @since TBD Serves the webhook route, which onboarding already registers with PayPal.
	 */
	public function register_endpoints() {
		$this->container->make( REST\Webhook_Endpoint::class )->register();
		$this->container->make( REST\On_Boarding_Endpoint::class )->register();
		$this->container->make( REST\Order_Endpoint::class )->register();
	}
}
