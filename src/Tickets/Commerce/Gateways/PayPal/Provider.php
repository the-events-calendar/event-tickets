<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use tad_DI52_ServiceProvider;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\PayPalClient;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\PayPalAuth;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\PayPalOrder;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK_Interface\RefreshToken;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK_Interface\Repositories\MerchantDetails;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK_Interface\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\PaymentCaptureCompleted;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\PaymentCaptureDenied;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\PaymentCaptureRefunded;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners\PaymentCaptureReversed;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhookRegister;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhooksRoute;
use Tribe\Tickets\REST\V1\Endpoints\Commerce\PayPal_Webhook;
use Tribe__Tickets__Main;

/**
 * Service provider for the Tickets Commerce: PayPal Commerce gateway.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		$this->container->singleton( Gateway::class );

		// @todo Is this needed?
		// $this->container->singleton( PaymentFormElements::class );

		/*$this->container->singleton( PaymentProcessor::class );
		$this->container->singleton( ScriptLoader::class );*/
		$this->container->singleton( AjaxRequestHandler::class );
		$this->container->singleton( onBoardingRedirectHandler::class );
		$this->container->singleton( RefreshToken::class );

		$this->container->singleton( PayPalAuth::class );
		$this->container->singleton( PayPalClient::class );
		$this->container->singleton( PayPalOrder::class );

		$this->container->singleton( MerchantDetail::class, null, [ 'init' ] );
		$this->container->singleton( MerchantDetails::class, null, [ 'init' ] );

		$this->container->singleton( WebhookRegister::class );
		$this->container->singleton( WebhooksRoute::class );
		$this->container->singleton( Webhooks::class, null, [ 'init' ] );

		$this->container->singleton( PaymentCaptureCompleted::class );
		$this->container->singleton( PaymentCaptureDenied::class );
		$this->container->singleton( PaymentCaptureRefunded::class );
		$this->container->singleton( PaymentCaptureReversed::class );

		$this->container->singleton( REST::class );
		$this->container->singleton(
			PayPal_Webhook::class,
			new PayPal_Webhook(
				tribe( 'tickets.rest-v1.messages' ),
				tribe( 'tickets.rest-v1.repository' ),
				tribe( 'tickets.rest-v1.validator' )
			)
		);

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

		add_action( 'init', [ $this, 'register_assets' ] );

		// Settings page: Connect PayPal.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_user_on_boarded', $this->container->callback( AjaxRequestHandler::class, 'onBoardedUserAjaxRequestHandler' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_get_partner_url', $this->container->callback( AjaxRequestHandler::class, 'onGetPartnerUrlAjaxRequestHandler' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_disconnect_account', $this->container->callback( AjaxRequestHandler::class, 'removePayPalAccount' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_onboarding_trouble_notice', $this->container->callback( AjaxRequestHandler::class, 'onBoardingTroubleNotice' ) );
		add_action( 'admin_init', $this->container->callback( onBoardingRedirectHandler::class, 'boot' ) );

		// Frontend: PayPal Checkout.
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_create_order', $this->container->callback( AjaxRequestHandler::class, 'createOrder' ) );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_create_order', $this->container->callback( AjaxRequestHandler::class, 'createOrder' ) );
		add_action( 'wp_ajax_tribe_tickets_paypal_commerce_approve_order', $this->container->callback( AjaxRequestHandler::class, 'approveOrder' ) );
		add_action( 'wp_ajax_nopriv_tribe_tickets_paypal_commerce_approve_order', $this->container->callback( AjaxRequestHandler::class, 'approveOrder' ) );

		// REST API Endpoint registration.
		add_action( 'rest_api_init', $this->container->callback( REST::class, 'register_endpoints' ) );

		// @todo Refund links?
	}

	/**
	 * Register assets needed for PayPal Commerce.
	 *
	 * @since TBD
	 */
	public function register_assets() {
		// @todo Add asset for frontend checkout page.

		tribe_asset(
			Tribe__Tickets__Main::instance(),
			'tribe-tickets-admin-commerce-paypal-commerce-partner-js',
			$this->get_partner_js_url(),
			[],
			'admin_enqueue_scripts',
			[
				'localize' => [
					[
						'name' => 'tribeTicketsCommercePayPaCommerce',
						'data' => [
							'translations' => [
								'confirmPaypalAccountDisconnection' => esc_html__( 'Disconnect PayPal Account', 'event-tickets' ),
								'disconnectPayPalAccount'           => esc_html__( 'Are you sure you want to disconnect your PayPal account?', 'event-tickets' ),
								'connectSuccessTitle'               => esc_html__( 'You’re connected to PayPal! Here’s what’s next...', 'event-tickets' ),
								'pciWarning'                        => sprintf(
									__(
										'PayPal allows you to accept credit or debit cards directly on your website. Because of
										this, your site needs to maintain <a href="%1$s" target="_blank">PCI-DDS compliance</a>.
										Event Tickets never stores sensitive information like card details to your server and works
										seamlessly with SSL certificates. Compliance is comprised of, but not limited to:', 'event-tickets'
									),
									// @todo Replace this URL.
									'https://www.theeventscalendar.com/documentation/resources/pci-compliance/'
								),
								'pciComplianceInstructions'         => [
									esc_html__( 'Using a trusted, secure hosting provider – preferably one which claims and actively promotes PCI compliance.', 'event-tickets' ),
									esc_html__( 'Maintain security best practices when setting passwords and limit access to your server.', 'event-tickets' ),
									esc_html__( 'Implement an SSL certificate to keep your payments secure.', 'event-tickets' ),
									esc_html__( 'Keep plugins up to date to ensure latest security fixes are present.', 'event-tickets' ),
								],
								'liveWarning'                       => tribe_tickets_commerce_is_test_mode()
									? esc_html__( 'You have connected your account for test mode. You will need to connect again once you are in live mode.', 'event-tickets' )
									: '',
							],
						],
					],
				],
			]
		);
	}

	/**
	 * Register REST API endpoints.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
	}

	/**
	 * Get PayPal partner JS asset url.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	private function get_partner_js_url() {
		/** @var PayPalClient $client */
		$client = tribe( PayPalClient::class );

		return sprintf(
			'%1$swebapps/merchantboarding/js/lib/lightbox/partner.js',
			$client->getHomePageUrl()
		);
	}
}
