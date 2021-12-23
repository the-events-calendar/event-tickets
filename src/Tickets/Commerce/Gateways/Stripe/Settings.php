<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Abstract_Settings;
use Tribe__Tickets__Main;

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
		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'plugin_url'            => Tribe__Tickets__Main::instance()->plugin_url,
//			'merchant'              => $merchant,
//			'is_merchant_connected' => $merchant->is_connected(),
//			'is_merchant_active'    => $merchant->is_active(),
//		'signup'                => $signup,
		];

		$admin_views->add_template_globals( $context );

		return $admin_views->template( 'settings/tickets-commerce/stripe/main', [], false );
	}
}