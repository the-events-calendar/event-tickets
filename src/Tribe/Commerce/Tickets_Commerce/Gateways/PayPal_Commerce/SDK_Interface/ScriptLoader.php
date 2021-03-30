<?php

namespace TEC\PaymentGateways\PayPalCommerce\SDK_Interface;

use TEC\PaymentGateways\PayPalCommerce\SDK_Interface\Utils;
use TEC\PaymentGateways\PayPalCommerce\SDK\Models\MerchantDetail;
use TEC\PaymentGateways\PayPalCommerce\Repositories\MerchantDetails;

// @todo Replace class usage.
use Give_Admin_Settings;

/**
 * Class ScriptLoader
 *
 * @since TBD
 * @package TEC\PaymentGateways\PayPalCommerce
 *
 */
class ScriptLoader {

	/**
	 * @since TBD
	 *
	 * @var MerchantDetails
	 */
	private $merchantRepository;

	/**
	 * ScriptLoader constructor.
	 *
	 * @since TBD
	 *
	 * @param MerchantDetails $merchantRepository
	 */
	public function __construct( MerchantDetails $merchantRepository ) {
		$this->merchantRepository = $merchantRepository;
	}

	/**
	 * Load admin scripts
	 *
	 * @since TBD
	 */
	public function loadAdminScripts() {
		// @todo Use Tribe Assets code.

		// @todo Check if on the admin script page.
		if ( ! Give_Admin_Settings::is_setting_page( 'gateway', 'paypal' ) ) {
			return;
		}

		wp_enqueue_script( 'give-paypal-partner-js', $this->getPartnerJsUrl(), [], null, true );

		// @todo Check if any of this CSS is required for PayPal Commerce admin stuff, outside of our own interfaces.
		wp_enqueue_style( 'give-admin-paypal-commerce-css', GIVE_PLUGIN_URL . 'assets/dist/css/admin-paypal-commerce.css', [], GIVE_VERSION );

		wp_localize_script(
			'give-paypal-partner-js',
			'givePayPalCommerce',
			[
				'translations' => [
					'confirmPaypalAccountDisconnection' => esc_html__( 'Disconnect PayPal Account', 'event-tickets' ),
					'disconnectPayPalAccount'           => esc_html__( 'Are you sure you want to disconnect your PayPal account?', 'event-tickets' ),
					'connectSuccessTitle'               => esc_html__( 'You’re connected to PayPal! Here’s what’s next...', 'event-tickets' ),
					'pciWarning'                        => sprintf(
						__( 'PayPal allows you to accept credit or debit cards directly on your website. Because of
							this, your site needs to maintain <a href="%1$s" target="_blank">PCI-DDS compliance</a>.
							GiveWP never stores sensitive information like card details to your server and works
							seamlessly with SSL certificates. Compliance is comprised of, but not limited to:', 'event-tickets' ),
						'https://givewp.com/documentation/resources/pci-compliance/'
					),
					'pciComplianceInstructions'         => [
						esc_html__( 'Using a trusted, secure hosting provider – preferably one which claims and actively promotes PCI compliance.', 'event-tickets' ),
						esc_html__( 'Maintain security best practices when setting passwords and limit access to your server.', 'event-tickets' ),
						esc_html__( 'Implement an SSL certificate to keep your donations secure.', 'event-tickets' ),
						esc_html__( 'Keep plugins up to date to ensure latest security fixes are present.', 'event-tickets' ),
					],
					'liveWarning'                       => give_is_test_mode()
						? esc_html__( 'You have connected your account for test mode. You will need to connect again once you are in live mode.', 'event-tickets' )
						: '',
				],
			]
		);

		$script = <<<EOT
				function givePayPalOnBoardedCallback(authCode, sharedId) {
					const query = '&authCode=' + authCode + '&sharedId=' + sharedId;
					fetch( ajaxurl + '?action=TBD_paypal_commerce_user_on_boarded' + query )
						.then(function(res){ return res.json() })
						.then(function(res) {
							if ( true !== res.success ) {
								alert('Something went wrong!');
								return;
							}

							// Remove PayPal quick help container.
							const paypalErrorQuickHelp = document.getElementById('give-paypal-onboarding-trouble-notice');
							paypalErrorQuickHelp && paypalErrorQuickHelp.remove();
						});
				}
EOT;

		wp_add_inline_script( 'give-paypal-partner-js', $script );
	}

	/**
	 * Load public assets.
	 *
	 * @since TBD
	 */
	public function loadPublicAssets() {
		if ( ! Utils::gatewayIsActive() || ! Utils::isAccountReadyToAcceptPayment() ) {
			return;
		}

		/* @var MerchantDetail $merchant */
		$merchant = tribe( MerchantDetail::class );
		$scriptId = 'give-paypal-commerce-js';

		/**
		 * List of PayPal query parameters: https://developer.paypal.com/docs/checkout/reference/customize-sdk/#query-parameters
		 */
		$payPalSdkQueryParameters = [
			'client-id'                   => $merchant->clientId,
			'merchant-id'                 => $merchant->merchantIdInPayPal,
			'components'                  => 'hosted-fields,buttons',
			'locale'                      => get_locale(),
			'disable-funding'             => 'credit',
			'vault'                       => true,
			'data-partner-attribution-id' => Give( 'PAYPAL_COMMERCE_ATTRIBUTION_ID' ),
			'data-client-token'           => $this->merchantRepository->getClientToken(),
		];

		wp_enqueue_script( $scriptId, GIVE_PLUGIN_URL . 'assets/dist/js/paypal-commerce.js', [], GIVE_VERSION, true );

		wp_localize_script(
			$scriptId,
			'givePayPalCommerce',
			[
				'paypalCardInfoErrorPrefixes'           => [
					'expirationDateField' => esc_html__( 'Card Expiration Date:', 'event-tickets' ),
					'cardNumberField'     => esc_html__( 'Card Number:', 'event-tickets' ),
					'cardCvcField'        => esc_html__( 'Card CVC:', 'event-tickets' ),
				],
				'cardFieldPlaceholders'                 => [
					'cardNumber'     => esc_html__( 'Card Number', 'event-tickets' ),
					'cardCvc'        => esc_html__( 'CVC', 'event-tickets' ),
					'expirationDate' => esc_html__( 'MM/YY', 'event-tickets' ),
				],
				'threeDsCardAuthenticationFailedNotice' => esc_html__( 'There was a problem authenticating your payment method. Please try again. If the problem persists, please try another payment method.', 'event-tickets' ),
				'errorCodeLabel'                        => esc_html__( 'Error Code', 'event-tickets' ),
				'genericDonorErrorMessage'              => __( 'There was an error processing your donation. Please contact the administrator.', 'event-tickets' ),
				// List of style properties support by PayPal for advanced card fields: https://developer.paypal.com/docs/business/checkout/reference/style-guide/#style-the-card-payments-fields
				'hostedCardFieldStyles'                 => apply_filters( 'give_paypal_commerce_hosted_field_style', [] ),
				'supportsCustomPayments'                => $merchant->supportsCustomPayments ? 1 : '',
				'accountCountry'                        => $merchant->accountCountry,
				'separatorLabel'                        => esc_html__( 'Or pay with card', 'event-tickets' ),
				'payPalSdkQueryParameters'              => $payPalSdkQueryParameters,
				'textForOverlayScreen'                  => sprintf(
					'<h3>%1$s</h3><p>%2$s</p><p>%3$s</p>',
					esc_html__( 'Donation Processing...', 'event-tickets' ),
					esc_html__( 'Checking donation status with PayPal.', 'event-tickets' ),
					esc_html__( 'This will only take a second!', 'event-tickets' )
				),
			]
		);
	}

	/**
	 * Get PayPal partner js url.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	private function getPartnerJsUrl() {
		return sprintf(
			'%1$swebapps/merchantboarding/js/lib/lightbox/partner.js',
			tribe( PayPalClient::class )->getHomePageUrl()
		);
	}
}
