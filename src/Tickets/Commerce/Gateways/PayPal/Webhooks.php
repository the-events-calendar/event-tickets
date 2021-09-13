<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\REST\Webhook_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Events;
use Tribe__Utils__Array as Arr;

/**
 * Class Webhooks
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Webhooks {

	/**
	 * Returns the options key for webhook settings in the merchant mode.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_settings_key() {
		$gateway_key   = Gateway::get_key();
		$merchant_mode = tribe( Merchant::class )->get_mode();

		return "tec_tickets_commerce_{$gateway_key}_{$merchant_mode}_webhooks_settings";
	}

	/**
	 * Retrieves the settings for the webhooks from the database.
	 *
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
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
	 * @since TBD
	 *
	 * @return bool
	 */
	public function delete_settings() {
		return delete_option( $this->get_settings_key() );
	}

	/**
	 * Creates or updates the Webhook associated with this site.
	 *
	 * @since TBD
	 *
	 * @return bool|\WP_Error
	 */
	public function create_or_update_existing() {
		$client       = tribe( Client::class );
		$existing_id  = $this->get_setting( 'id' );

		// When we dont have a webhook we try to create.
		if ( ! $existing_id ) {
			$webhook = $client->create_webhook();
			// Update the settings if a new webhook was created.
			if ( ! is_wp_error( $webhook ) ) {
				return $this->update_settings( $webhook );
			}

			// Returns the failed webhook creation or update.
			return $webhook;
		}

		$needs_update = false;

		$url = $this->get_setting( 'url' );

		if ( $url !== tribe( Webhook_Endpoint::class )->get_route_url() ) {
			$needs_update = true;
		}

		$event_types = wp_list_pluck( $this->get_setting( 'event_types' ), 'name' );

		$has_diff_events = array_diff( tribe( Events::class )->get_registered_events(), $event_types );
		if ( ! empty( $has_diff_events ) ) {
			$needs_update = true;
		}

		// If no update is needed we bail with success.
		if ( ! $needs_update ) {
			return true;
		}

		$webhook = $client->update_webhook( $existing_id );
		// Update the settings if the webhook was updated.
		if ( ! is_wp_error( $webhook ) ) {
			return $this->update_settings( $webhook );
		}

		$webhook = $client->create_webhook();
		// Update the settings if a new webhook was created.
		if ( ! is_wp_error( $webhook ) ) {
			return $this->update_settings( $webhook );
		}

		// Returns the failed webhook creation or update.
		return $webhook;
	}
}