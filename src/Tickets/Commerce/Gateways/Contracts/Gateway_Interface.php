<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Gateway Interface
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface Gateway_Interface {

	/**
	 * Get's the key for this Commerce Gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return string What is the Key used.
	 */
	public static function get_key();

	/**
	 * Get the provider key for this Commerce Gateway.
	 *
	 * @since 5.1.9
	 *
	 * @return string What is the ORM Provider Key used.
	 */
	public static function get_provider_key();

	/**
	 * Get the label for this Commerce Gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return string What label we are using for this gateway.
	 */
	public static function get_label();

	/**
	 * Get the settings url for this Commerce Gateway section.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public static function get_settings_url();

	/**
	 * Get the list of settings for the gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return array The list of settings for the gateway.
	 */
	public function get_settings();

	/**
	 * Determine whether the gateway is active.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether the gateway is active.
	 */
	public static function is_active();

	/**
	 * Determine whether the gateway is connected.
	 *
	 * @since 5.2.0
	 *
	 * @return bool Whether the gateway is connected.
	 */
	public static function is_connected();

	/**
	 * Determine whether the gateway should be shown as an available gateway.
	 *
	 * @since 5.1.6
	 *
	 * @return bool Whether the gateway should be shown as an available gateway.
	 */
	public static function should_show();

	/**
	 * Register the gateway for Tickets Commerce.
	 *
	 * @since 5.1.6
	 *
	 * @param array $gateways The list of registered Tickets Commerce gateways.
	 *
	 * @return Abstract_Gateway[] The list of registered Tickets Commerce gateways.
	 */
	public function register_gateway( array $gateways );

	/**
	 * Get all the admin notices.
	 *
	 * @since 5.2.0.
	 *
	 * @return array
	 */
	public function get_admin_notices();

	/**
	 * Displays error notice for invalid API responses, with error message from API response data.
	 *
	 * @since 5.2.0
	 *
	 * @param array  $response Raw Response data.
	 * @param string $message  Additional message to show with error message.
	 * @param string $slug     Slug for notice container.
	 */
	public function handle_invalid_response( $response, $message, $slug = 'error' );

	/**
	 * Renders the template for the checkout.
	 *
	 * @since 5.3.0
	 *
	 * @param \Tribe__Template $template Template used to render the checkout.
	 *
	 * @return string
	 */
	public function render_checkout_template( \Tribe__Template $template ): string;
}