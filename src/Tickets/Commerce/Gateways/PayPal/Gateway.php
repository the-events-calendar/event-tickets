<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK_Interface\Repositories\MerchantDetails;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal_Main;

/**
 * Class Gateway
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Gateway extends Abstract_Gateway {
	/**
	 * @inheritDoc
	 */
	protected static $key = 'paypal-commerce';

	/**
	 * PayPal attribution ID for requests.
	 *
	 * @since TBD
	 *
	 * @const
	 */
	const ATTRIBUTION_ID = 'TheEventsCalendar_SP_PPCP';

	/**
	 * @inheritDoc
	 */
	public static function get_label() {
		return __( 'PayPal Commerce', 'event-tickets' );
	}

	/**
	 * @inheritDoc
	 */
	public static function is_active() {
		// If this gateway shouldn't be shown, then don't change the active status.
		if ( ! static::should_show() ) {
			return false;
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
