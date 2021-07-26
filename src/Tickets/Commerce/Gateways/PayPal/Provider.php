<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\Models\Status_Manager;

/**
 * Service provider for the Tickets Commerce: PayPal Commerce gateway.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		$this->container->singleton( Gateway::class );

		$this->register_hooks();
		$this->register_assets();

		// @todo Is this needed?
		// $this->container->singleton( PaymentFormElements::class );
		// $this->container->singleton( PaymentProcessor::class );

		$this->container->singleton( Merchant::class, Merchant::class, [ 'init' ] );

		$this->container->singleton( Ajax_Request_Handler::class );
		$this->container->singleton( On_Boarding_Redirect_Handler::class );
		$this->container->singleton( Refresh_Token::class );
		$this->container->singleton( Client::class );

		$this->container->singleton( Repositories\Authorization::class );
		$this->container->singleton( Repositories\Order::class );
		$this->container->singleton( Repositories\Webhooks::class );

		$this->container->singleton( Webhooks\Webhook_Register::class );
		$this->container->singleton( Webhooks\Webhooks_Route::class );

		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Completed::class );
		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Denied::class );
		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Refunded::class );
		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Reversed::class );

		$this->register_endpoints();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider
	 *
	 * @since 5.1.6
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.1.6
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @since 5.1.6
	 */
	public function register_endpoints() {
		$hooks = new REST( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( REST::class, $hooks );
	}

}
