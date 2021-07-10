<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use Tribe\Tickets\REST\V1\Endpoints\Commerce\PayPal_Webhook;

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
		$this->container->singleton( AjaxRequestHandler::class );
		$this->container->singleton( onBoardingRedirectHandler::class );
		$this->container->singleton( SDK\RefreshToken::class );

		$this->container->singleton( SDK\Repositories\PayPalAuth::class );
		$this->container->singleton( SDK\PayPalClient::class );
		$this->container->singleton( SDK\Repositories\PayPalOrder::class );

		$this->container->singleton( SDK\Models\MerchantDetail::class, null, [ 'init' ] );
		$this->container->singleton( SDK\Repositories\MerchantDetails::class, null, [ 'init' ] );

		$this->container->singleton( Webhooks\WebhookRegister::class );
		$this->container->singleton( Webhooks\WebhooksRoute::class );
		$this->container->singleton( SDK\Repositories\Webhooks::class, null, [ 'init' ] );

		$this->container->singleton( Webhooks\Listeners\PaymentCaptureCompleted::class );
		$this->container->singleton( Webhooks\Listeners\PaymentCaptureDenied::class );
		$this->container->singleton( Webhooks\Listeners\PaymentCaptureRefunded::class );
		$this->container->singleton( Webhooks\Listeners\PaymentCaptureReversed::class );

		$this->container->singleton( REST::class );
		$this->container->singleton( PayPal_Webhook::class, static function() {
			return new PayPal_Webhook(
				tribe( 'tickets.rest-v1.messages' ),
				tribe( 'tickets.rest-v1.repository' ),
				tribe( 'tickets.rest-v1.validator' )
			);
		} );
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
