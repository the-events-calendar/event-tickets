<?php

namespace TEC\PaymentGateways\PayPalCommerce\SDK_Interface;

// @todo Replace class usage.
use Give_Admin_Settings;
use TEC\PaymentGateways\PayPalCommerce\Repositories\MerchantDetails;
use TEC\PaymentGateways\PayPalCommerce\SDK_Interface\Utils;

/**
 * Account admin notices for PayPal Commerce.
 *
 * @since TBD
 *
 * @package TEC\PaymentGateways\PayPalCommerce\SDK_Interface
 */
class AccountAdminNotices {

	/**
	 * The merchant repository object.
	 *
	 * @since TBD
	 *
	 * @var MerchantDetails
	 */
	private $merchantRepository;

	/**
	 * AccountAdminNotices constructor.
	 *
	 * @param MerchantDetails $merchantRepository
	 */
	public function __construct( MerchantDetails $merchantRepository ) {
		$this->merchantRepository = $merchantRepository;
	}

	/**
	 * Displays the admin notices in the right conditions.
	 *
	 * @since TBD
	 */
	public function displayNotices() {
		// @todo Add something to ET to check if a provider is in test/sandbox mode.
		if ( Utils::gatewayIsActive() && ! give_is_test_mode() ) {
			if ( $this->merchantRepository->accountIsConnected() ) {
				// If account is connected, check if it is ready.
				$this->checkForAccountReadiness();
			} else {
				// If the account is not connected, prompt to connect.
				$this->checkForConnectedLiveAccount();
			}
		}
	}

	/**
	 * Displays a notice if the account is not connected.
	 *
	 * @since TBD
	 */
	public function checkForConnectedLiveAccount() {
		$this->add_notice(
			esc_html__( 'Please connect to your account so payments may be processed.', 'event-tickets' ),
			esc_html__( 'Connect Account', 'event-tickets' )
		);
	}

	/**
	 * Displays a notice if the account is connected but not ready.
	 *
	 * @since TBD
	 */
	public function checkForAccountReadiness() {
		$merchantDetails = $this->merchantRepository->getDetails();

		if ( $merchantDetails->accountIsReady ) {
			return;
		}

		$this->add_notice(
			esc_html__( 'Please check your account status as additional setup is needed before you may accept payments.', 'event-tickets' ),
			esc_html__( 'Account Status', 'event-tickets' )
		);
	}

	/**
	 * Displays a notice if the account is connected but not ready.
	 *
	 * @since TBD
	 *
	 * @param string $message The notice message.
	 * @param string $action  The action text to use for the link.
	 */
	public function add_notice( $message, $action ) {
		// @todo Replace the URL here.
		$connect_url = admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=paypal' );

		$notice = sprintf(
			'<strong>%1$s</strong> %2$s <a href="%3$s">%4$s</a>',
			esc_html__( 'Tickets Commerce: PayPal Commerce', 'event-tickets' ),
			esc_html( $message ),
			esc_url( $connect_url ),
			esc_html( $action )
		);

		tribe_notice( __FUNCTION__, $notice, [
			'dismiss' => true,
			'type'    => 'warning',
		] );
	}
}
