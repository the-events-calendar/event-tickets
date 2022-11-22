<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use Tribe__Utils__Array as Arr;

/**
 * Class Abstract_Webhooks.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Webhooks {

	/**
	 * Gets the gateway for this webhook.
	 *
	 * @since 5.3.0
	 *
	 * @return Abstract_Gateway
	 */
	abstract public function get_gateway() : Abstract_Gateway;

	/**
	 * Gets the merchant for this webhook.
	 *
	 * @since 5.3.0
	 *
	 * @return Abstract_Merchant
	 */
	abstract public function get_merchant() : Abstract_Merchant;

	/**
	 * Returns the options key for webhook settings in the merchant mode.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_settings_key() : string {
		$gateway = $this->get_gateway();
		$gateway_key   = $gateway::get_key();
		$merchant_mode = $this->get_merchant()->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_{$merchant_mode}_webhooks_settings";
	}

	/**
	 * Retrieves the settings for the webhooks from the database.
	 *
	 * @since 5.3.0
	 *
	 * @param array|string $key       Specify each nested index in order.
	 *                                Example: array( 'lvl1', 'lvl2' );
	 * @param mixed        $default   Default value if the search finds nothing.
	 *
	 * @return mixed
	 */
	public function get_setting( $key, $default = null ) {
		$settings = get_option( $this->get_settings_key(), null );

		return Arr::get( $settings, $key, $default );
	}

	/**
	 * Saves the webhook settings in the database.
	 *
	 * @since 5.3.0
	 *
	 * @param array $settings []
	 *
	 * @return bool
	 */
	public function update_settings( array $settings = [] ) {
		return update_option( $this->get_settings_key(), $settings );
	}

	/**
	 * Retrieves the settings for the webhooks from the database.
	 *
	 * @since 5.3.0
	 *
	 * @return array|null
	 */
	public function get_settings() {
		$settings = get_option( $this->get_settings_key(), null );

		// Without an ID, the webhook settings are invalid.
		if ( empty( $settings['id'] ) ) {
			return null;
		}

		return $settings;
	}

	/**
	 * Deletes the stored webhook settings.
	 *
	 * @since 5.3.0
	 *
	 * @return bool
	 */
	public function delete_settings() {
		return delete_option( $this->get_settings_key() );
	}

}