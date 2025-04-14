<?php

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Settings as TC_Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Utils__Array as Arr;

/**
 * Square Commerce Settings.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Settings {

	/**
	 * Client ID option key.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_client_id = 'tickets-commerce-square-client-id';

	/**
	 * Sandbox Client ID option key.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_sandbox_client_id = 'tickets-commerce-square-sandbox-client-id';

	/**
	 * Location ID option key.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_location_id = 'tickets-commerce-square-location-id';

	/**
	 * Sandbox Location ID option key.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_sandbox_location_id = 'tickets-commerce-square-sandbox-location-id';

	/**
	 * Test mode option key.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $option_test_mode = 'tickets-commerce-square-test-mode';

	/**
	 * Get all the settings for the Square gateway.
	 *
	 * @since TBD
	 *
	 * @return array The gateway settings.
	 */
	public function get_settings() {
		$settings_array = [
			'square-connection-start' => [
				'type'            => 'html',
				'html'            => '<div class="tec-tickets__admin-settings-toggle-large">',
			],
			'square-signup' => [
				'type'            => 'html',
				'html'            => $this->get_connection_settings_html(),
			],
			'square-connection-end' => [
				'type'            => 'html',
				'html'            => '</div>',
			],
		];

		return $settings_array;
	}

	/**
	 * Get the connection settings HTML.
	 *
	 * @since TBD
	 *
	 * @return false|string
	 */
	public function get_connection_settings_html() {
		$merchant      = tribe( Merchant::class );
		$is_connected  = $merchant->is_connected();
		$signup_template  = 'signup';

		if ( $is_connected ) {
			$signup_template = 'connected';
		}

		$admin_views = tribe( 'tickets.admin.views' );

		// Configure variables for the template.
		$template_vars = [
			'merchant'         => $merchant,
			'gateway'          => tribe( Gateway::class ),
			'settings_url'     => tribe( Payments_Tab::class )->get_url(),
			'disconnect_nonce' => wp_create_nonce( $merchant->get_disconnect_action() ),
			'is_connected'     => $is_connected,
		];

		// Only add these if we're connected.
		if ( $is_connected && false === true ) {
			$connected_vars = [
				'merchant_name'     => $merchant->get_merchant_name(),
				'merchant_email'    => $merchant->get_merchant_email(),
				'merchant_id'       => $merchant->get_merchant_id(),
				'merchant_currency' => $merchant->get_merchant_currency(),
			];

			$template_vars = array_merge( $template_vars, $connected_vars );
		}

		return $admin_views->template( 'settings/tickets-commerce/square/' . $signup_template, $template_vars, false );
	}

	/**
	 * Get section name for the gateway.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_section_name() {
		return 'square-payments';
	}

	/**
	 * Filter the container classes for the settings page when Square is active.
	 *
	 * @since TBD
	 *
	 * @param array $container_classes
	 * @param string $section
	 *
	 * @return array
	 */
	public function filter_settings_container_classes( $container_classes, $section ) {
		if ( $this->get_section_name() === $section ) {
			$container_classes = array_merge(
				$container_classes,
				[
					'tribe-tickets__admin-container--square-payments',
					'tribe-tickets__admin-container',
					'tribe-common',
				]
			);
		}

		return $container_classes;
	}
}
