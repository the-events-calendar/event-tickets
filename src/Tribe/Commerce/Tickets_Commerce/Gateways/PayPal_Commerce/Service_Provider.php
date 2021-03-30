<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Give\Controller\PayPalWebhooks;
use Give\PaymentGateways\PayPalCommerce\onBoardingRedirectHandler;
use Give\PaymentGateways\PayPalCommerce\Repositories\Webhooks;
use Give\PaymentGateways\Stripe\DonationFormElements;
use tad_DI52_ServiceProvider;

/**
 * Service provider for the Tickets Commerce: PayPal Commerce gateway.
 *
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 * @since   TBD
 */
class Service_Provider extends tad_DI52_ServiceProvider {
	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		give()->bind(
			'PAYPAL_COMMERCE_ATTRIBUTION_ID',
			static function() {
				return 'GiveWP_SP_PCP';
			}
		); // storage

		give()->singleton( PayPalWebhooks::class );
		give()->singleton( Webhooks::class );
		give()->singleton( DonationFormElements::class );

		$this->container->singleton( AdvancedCardFields::class, AdvancedCardFields::class );
		$this->container->singleton( DonationProcessor::class, DonationProcessor::class );
		$this->container->singleton( PayPalClient::class, PayPalClient::class );
		$this->container->singleton( RefreshToken::class, RefreshToken::class );
		$this->container->singleton( AjaxRequestHandler::class, AjaxRequestHandler::class );
		$this->container->singleton( ScriptLoader::class, ScriptLoader::class );
		$this->container->singleton( WebhookRegister::class, WebhookRegister::class );
		$this->container->singleton( PayPalAuth::class, PayPalAuth::class );

		$this->container->singleton(
			MerchantDetail::class,
			MerchantDetail::class,
			static function () {
				/** @var MerchantDetails $repository */
				$repository = give( MerchantDetails::class );

				return $repository->getDetails();
			}
		);

		$this->container->singleton(
			MerchantDetails::class,
			MerchantDetails::class,
			static function ( MerchantDetails $details ) {
				// @todo Replace give_is_test_mode() with something for the gateway.
				$details->setMode( give_is_test_mode() ? 'sandbox' : 'live' );
			}
		);

		$this->container->singleton(
			Webhooks::class,
			Webhooks::class,
			static function ( Webhooks $repository ) {
				// @todo Replace give_is_test_mode() with something for the gateway.
				$repository->setMode( give_is_test_mode() ? 'sandbox' : 'live' );
			}
		);

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		// @todo Replace the filter here.
		add_filter( 'give_register_gateway', [ $this, 'register_gateway' ] );

		add_action( 'admin_init', tribe_callback( onBoardingRedirectHandler::class, 'boot' ) );
	}
}
