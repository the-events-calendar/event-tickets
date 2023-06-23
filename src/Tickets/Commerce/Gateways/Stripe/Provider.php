<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Account_Webhook;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Charge_Webhook;
use TEC\Tickets\Commerce\Gateways\Stripe\Webhooks\Payment_Intent_Webhook;

/**
 * Class Provider
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Provider extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->container->singleton( Gateway::class );
		$this->container->singleton( Merchant::class );
		$this->container->singleton( REST::class );
		$this->container->singleton( Settings::class );
		$this->container->singleton( Signup::class );
		$this->container->singleton( Stripe_Elements::class );
		$this->container->singleton( Status::class );
		$this->container->singleton( WhoDat::class );
		$this->container->singleton( Webhooks::class );
		$this->container->singleton( Payment_Intent_Handler::class );

		// Webhooks.
		$this->container->singleton( Account_Webhook::class );
		$this->container->singleton( Charge_Webhook::class );
		$this->container->singleton( Payment_Intent_Webhook::class );

		$this->register_hooks();
		$this->register_assets();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.3.0
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider
	 *
	 * @since 5.3.0
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}
}
