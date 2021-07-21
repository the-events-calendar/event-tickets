<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

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

		/*$this->container->singleton( PaymentProcessor::class );;*/
		$this->container->singleton( Ajax_Request_Handler::class );
		$this->container->singleton( On_Boarding_Redirect_Handler::class );
		$this->container->singleton( Refresh_Token::class );

		$this->container->singleton( Repositories\PayPal_Auth::class );
		$this->container->singleton( PayPal_Client::class );
		$this->container->singleton( Repositories\PayPal_Order::class );

		$this->container->singleton( Models\Merchant_Detail::class, null, [ 'init' ] );
		$this->container->singleton( Repositories\Merchant_Details::class, null, [ 'init' ] );

		$this->container->singleton( Webhooks\Webhook_Register::class );
		$this->container->singleton( Webhooks\Webhooks_Route::class );
		$this->container->singleton( Repositories\Webhooks::class, null, [ 'init' ] );

		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Completed::class );
		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Denied::class );
		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Refunded::class );
		$this->container->singleton( Webhooks\Listeners\Payment_Capture_Reversed::class );

		$this->container->singleton( REST::class );
		$this->container->singleton( REST\PayPal_Webhook::class, REST\PayPal_Webhook::class );
		$this->container->singleton( REST\On_Boarding::class, REST\On_Boarding::class );
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

	}

}
