<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal;

use TEC\Tickets\Commerce\Gateways\PayPal\REST\Webhook_Endpoint;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Events;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Gateway;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Merchant;
use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_Webhooks;

use Tribe__Utils__Array as Arr;

/**
 * Class Webhooks
 *
 * @since 5.1.10
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Webhooks extends Abstract_Webhooks {

	/**
	 * @inheritDoc
	 */
	public function get_gateway(): Abstract_Gateway {
		return tribe( Gateway::class );
	}

	/**
	 * @inheritDoc
	 */
	public function get_merchant(): Abstract_Merchant {
		return tribe( Merchant::class );
	}

	/**
	 * Creates or updates the Webhook associated with this site.
	 *
	 * @since 5.1.10
	 *
	 * @return bool|\WP_Error
	 */
	public function create_or_update_existing() {
		$client      = tribe( Client::class );
		$existing_id = $this->get_setting( 'id' );

		// When we dont have a webhook we try to create.
		if ( ! $existing_id ) {
			$webhook = $client->create_webhook();
			// Update the settings if a new webhook was created.
			if ( ! is_wp_error( $webhook ) ) {
				return $this->update_settings( $webhook );
			}

			if ( 'tec-tickets-commerce-gateway-paypal-webhook-url-already-exists' === $webhook->get_error_code() ) {
				$error_message     = $webhook->get_error_message();
				$existing_webhooks = $client->list_webhooks();
				$existing_webhooks = array_filter( array_map( static function ( $webhook ) {
					if ( tribe( Webhook_Endpoint::class )->get_route_url() !== $webhook['url'] ) {
						return null;
					}

					return $webhook;
				}, $existing_webhooks ) );
				if ( empty( $existing_webhooks ) ) {
					return new \WP_Error( 'tec-tickets-commerce-gateway-paypal-webhook-unexpected-update-create', $error_message );
				}
				$existing_webhook = current( $existing_webhooks );

				if ( ! $this->needs_update( $existing_webhook ) ) {
					// We found a existing webhook that matched the URL but we just save it to the DB since it was up-to-date.
					return $this->update_settings( $existing_webhook );
				}

				$webhook = $client->update_webhook( $existing_id );
				// Update the settings if the webhook was updated.
				if ( ! is_wp_error( $webhook ) ) {
					return $this->update_settings( $webhook );
				}
			}

			// Returns the failed webhook creation or update.
			return $webhook;
		}

		if ( ! $this->needs_update() ) {
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

	/**
	 * Determines if a given webhook set needs to be updated based on the current values.
	 *
	 * @since 5.1.10
	 *
	 * @param array $webhook Current webhook set, if null will pull from DB.
	 *
	 * @return bool
	 */
	public function needs_update( $webhook = null ) {
		if ( ! is_array( $webhook ) ) {
			$webhook = $this->get_settings();
		}

		// If these are not valid indexes, we just say we need an update.
		if ( ! isset( $webhook['url'], $webhook['event_types'] ) ) {
			return true;
		}

		$url = Arr::get( $webhook, 'url' );

		if ( $url !== tribe( Webhook_Endpoint::class )->get_route_url() ) {
			return true;
		}

		$event_types = wp_list_pluck( Arr::get( $webhook, 'event_types' ), 'name' );

		$has_diff_events = array_diff( tribe( Events::class )->get_registered_events(), $event_types );
		if ( ! empty( $has_diff_events ) ) {
			return true;
		}

		return false;
	}
}
