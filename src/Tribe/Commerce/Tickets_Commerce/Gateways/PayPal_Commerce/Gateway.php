<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\Abstract_Gateway;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK_Interface\Repositories\MerchantDetails;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * Class Gateway
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 */
class Gateway extends Abstract_Gateway {

	/**
	 * The Gateway key.
	 *
	 * @since TBD
	 *
	 * @const
	 */
	const GATEWAY_KEY = 'paypal-commerce';

	/**
	 * PayPal attribution ID for requests.
	 *
	 * @since TBD
	 *
	 * @const
	 */
	const ATTRIBUTION_ID = 'TheEventsCalendar_SP_PPCP';

	/**
	 * Register the gateway for Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @param array       $gateways The list of registered Tickets Commerce gateways.
	 * @param PayPal_Main $commerce The Tickets Commerce provider.
	 *
	 * @return array The list of registered Tickets Commerce gateways.
	 */
	public function register_gateway( array $gateways, $commerce ) {
		$gateways['paypal-commerce'] = [
			'label'  => __( 'PayPal Commerce', 'event-tickets' ),
			'class'  => self::class,
			'object' => $this,
		];

		return $gateways;
	}

	/**
	 * Determine whether the provider is active depending on the gateway settings.
	 *
	 * @since TBD
	 *
	 * @param bool        $is_active Whether the provider is active.
	 * @param PayPal_Main $commerce  The Tickets Commerce provider.
	 *
	 * @return bool Whether the provider is active.
	 */
	public function is_active( $is_active, $commerce ) {
		// Bail if the provider is already showing as active.
		if ( $is_active ) {
			return $is_active;
		}

		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! $this->should_show( false, $commerce ) ) {
			return $is_active;
		}

		/** @var MerchantDetails $merchantDetails */
		$merchantDetails = tribe( MerchantDetails::class );

		// Make sure we have details setup.
		$merchantDetails->getDetails();

		// @todo Confirm this is the correct conditional.
		if ( $merchantDetails->accountIsConnected() ) {
			return true;
		}

		return false;
	}

	/**
	 * Get the list of settings for the gateway.
	 *
	 * @since TBD
	 *
	 * @return array The list of settings for the gateway.
	 */
	public function get_settings() {
		/** @var Settings $settings */
		$settings = tribe( Settings::class );

		return $settings->get_settings();
	}

}
