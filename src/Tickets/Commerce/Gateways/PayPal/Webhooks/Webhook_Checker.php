<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\Models\Merchant_Detail;
use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Webhooks_Route;

class Webhook_Checker {

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhooks_repository;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks_Route
	 */
	private $webhooks_route;

	/**
	 * @since 5.1.6
	 *
	 * @var Webhook_Register
	 */
	private $webhook_register;

	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Detail
	 */
	private $merchant_details;

	/**
	 * WebhookChecker constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Webhooks         $webhooks_repository
	 * @param Merchant_Detail  $merchant_details
	 * @param Webhooks_Route   $webhooks_route
	 * @param Webhook_Register $webhook_register
	 */
	public function __construct( Webhooks $webhooks_repository, Merchant_Detail $merchant_details, Webhooks_Route $webhooks_route, Webhook_Register $webhook_register ) {
		$this->webhooks_repository = $webhooks_repository;
		$this->merchant_details    = $merchant_details;
		$this->webhooks_route      = $webhooks_route;
		$this->webhook_register    = $webhook_register;
	}

	/**
	 * Checks whether the webhook configuration has changed. If it has, then update the webhook with PayPal.
	 *
	 * @since 5.1.6
	 */
	public function check_webhook_criteria() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! $this->merchant_details->access_token ) {
			return;
		}

		$webhook_config = $this->webhooks_repository->get_webhook_config();

		if ( $webhook_config === null ) {
			return;
		}

		$webhook_url       = $this->webhooks_route->get_route_url();
		$registered_events = $this->webhook_register->get_registered_events();

		$missing_events     = array_merge(
			array_diff( $registered_events, $webhook_config->events ),
			array_diff( $webhook_config->events, $registered_events )
		);
		$has_missing_events = ! empty( $missing_events );

		// Update the webhook if the return url or events have changed
		if ( $webhook_url !== $webhook_config->return_url || $has_missing_events ) {
			try {
				$this->webhooks_repository->update_webhook( $this->merchant_details->access_token, $webhook_config->id );

				$webhook_config->return_url = $webhook_url;
				$webhook_config->events     = $registered_events;

				$this->webhooks_repository->save_webhook_config( $webhook_config );
			} catch ( Exception $exception ) {
				// @todo Replace this with a notice / log.
				tribe( 'logger' )->log_error(
					__( 'There was a problem updating your PayPal Payments webhook. Please disconnect your account and reconnect it.', 'event-tickets' ),
					'tickets-commerce-paypal-commerce'
				);
			}
		}
	}
}
