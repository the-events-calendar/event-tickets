<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

/**
 * Class REST
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class REST extends \tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->container->singleton( REST\Order_Endpoint::class );
		$this->container->singleton( REST\Return_Endpoint::class );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->container->make( REST\Order_Endpoint::class )->register();
		$this->container->make( REST\Return_Endpoint::class )->register();
	}
}
