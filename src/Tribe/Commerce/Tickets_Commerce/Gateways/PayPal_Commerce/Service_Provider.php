<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use tad_DI52_ServiceProvider;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models\MerchantDetail;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\PayPalClient;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Repositories\PayPalAuth;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Repositories\MerchantDetails;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Repositories\Webhooks;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\ScriptLoader;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\WebhookRegister;

/**
 * Service provider for the Tickets Commerce: PayPal Commerce gateway.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 */
class Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( Gateway::class );

		// @todo Bring over Give\Controller\PayPalWebhooks.
		//$this->container->singleton( PayPalWebhooks::class );

		// @todo This isn't the same webhooks as registered later, look into that.
		// $this->container->singleton( Webhooks::class );

		// @todo Is this needed?
		// $this->container->singleton( PaymentFormElements::class );

		/*$this->container->singleton( PaymentProcessor::class );
		$this->container->singleton( PayPalClient::class );
		$this->container->singleton( RefreshToken::class );
		$this->container->singleton( AjaxRequestHandler::class );
		$this->container->singleton( ScriptLoader::class );
		$this->container->singleton( WebhookRegister::class );
		$this->container->singleton( PayPalAuth::class );*/
		$this->container->singleton( MerchantDetail::class, null, [ 'init' ] );
		$this->container->singleton( MerchantDetails::class, null, [ 'init' ] );
		//$this->container->singleton( Webhooks::class, null, [ 'init' ] );

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		add_filter( 'tribe_tickets_commerce_paypal_gateways', $this->container->callback( Gateway::class, 'register_gateway' ), 10, 2 );
		add_filter( 'tribe_tickets_commerce_paypal_is_active', $this->container->callback( Gateway::class, 'is_active' ), 9, 2 );

		// @todo Replace the filter here.
		// add_action( 'admin_init', $this->container->callback( onBoardingRedirectHandler::class, 'boot' ) );
	}

}
