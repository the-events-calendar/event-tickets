<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

/**
 * Class REST
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class REST extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->container->singleton( REST\Order_Endpoint::class );
		$this->container->singleton( REST\Return_Endpoint::class );
		$this->container->singleton( REST\Webhook_Endpoint::class );
	}

	/**
	 * Register the endpoints for handling webhooks.
	 *
	 * @since 5.3.0
	 */
	public function register_endpoints() {
		$this->container->make( REST\Order_Endpoint::class )->register();
		$this->container->make( REST\Return_Endpoint::class )->register();
		$this->container->make( REST\Webhook_Endpoint::class )->register();
	}
}
