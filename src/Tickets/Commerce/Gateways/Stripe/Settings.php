<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Abstract_Settings;

/**
 * The Stripe specific settings.
 *
 * @since TBD
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Settings extends Abstract_Settings {

	/**
	 * @inheritDoc
	 */
	public static $option_sandbox = 'tickets-commerce-stripe-sandbox';

	/**
	 * @inheritDoc
	 */
	public function get_settings() {
		// TODO: Implement get_settings() method.
	}
}