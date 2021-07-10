<?php
/**
 * Handles registering and setup for assets on Ticket Commerce.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

/**
 * Class Assets.
 *
 * @since 5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Assets extends \tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.1.6
	 */
	public function register() {
		tribe_asset(
			\Tribe__Tickets__Main::instance(),
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
	 * Get PayPal partner JS asset url.
	 *
	 * @since 5.1.6
	 *
	 * @return string
	 */
	private function get_partner_js_url() {
		/** @var PayPalClient $client */
		$client = tribe( SDK\PayPalClient::class );

		return sprintf(
			'%1$swebapps/merchantboarding/js/lib/lightbox/partner.js',
			$client->getHomePageUrl()
		);
	}
}