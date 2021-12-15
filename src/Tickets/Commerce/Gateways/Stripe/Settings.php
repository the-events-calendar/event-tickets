<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Abstract_Settings;

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
		return [
			'tickets-commerce-stripe-commerce-configure' => [
				'type'            => 'wrapped_html',
				'html'            => $this->get_connection_settings_html(),
				'validation_type' => 'html',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function get_connection_settings_html() {
		return 'hi';
	}
}