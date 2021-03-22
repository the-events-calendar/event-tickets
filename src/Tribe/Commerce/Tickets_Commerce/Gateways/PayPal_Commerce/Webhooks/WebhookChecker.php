<?php

namespace TEC\PaymentGateways\PayPalCommerce\Webhooks;

use Exception;
use Give_Admin_Settings;
use TEC\PaymentGateways\PayPalCommerce\SDK\Models\MerchantDetail;
use TEC\PaymentGateways\PayPalCommerce\Repositories\Webhooks;
use TEC\Route\PayPalWebhooks as WebhooksRoute;

class WebhookChecker {

	/**
	 * @since TBD
	 *
	 * @var Webhooks
	 */
	private $webhooksRepository;

	/**
	 * @since TBD
	 *
	 * @var WebhooksRoute
	 */
	private $webhooksRoute;

	/**
	 * @since TBD
	 *
	 * @var WebhookRegister
	 */
	private $webhookRegister;

	/**
	 * @since TBD
	 *
	 * @var MerchantDetail
	 */
	private $merchantDetails;

	/**
	 * WebhookChecker constructor.
	 *
	 * @since TBD
	 *
	 * @param Webhooks        $webhooksRepository
	 * @param MerchantDetail  $merchantDetails
	 * @param WebhooksRoute   $webhooksRoute
	 * @param WebhookRegister $webhookRegister
	 */
	public function __construct( Webhooks $webhooksRepository, MerchantDetail $merchantDetails, WebhooksRoute $webhooksRoute, WebhookRegister $webhookRegister ) {
		$this->webhooksRepository = $webhooksRepository;
		$this->merchantDetails    = $merchantDetails;
		$this->webhooksRoute      = $webhooksRoute;
		$this->webhookRegister    = $webhookRegister;
	}

	/**
	 * Checks whether the webhook configuration has changed. If it has, then update the webhook with PayPal.
	 *
	 * @since TBD
	 */
	public function checkWebhookCriteria() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		if ( ! $this->merchantDetails->accessToken ) {
			return;
		}

		$webhookConfig = $this->webhooksRepository->getWebhookConfig();

		if ( $webhookConfig === null ) {
			return;
		}

		$webhookUrl       = $this->webhooksRoute->getRouteUrl();
		$registeredEvents = $this->webhookRegister->getRegisteredEvents();

		$hasMissingEvents = ! empty( array_merge( array_diff( $registeredEvents, $webhookConfig->events ), array_diff( $webhookConfig->events, $registeredEvents ) ) );

		// Update the webhook if the return url or events have changed
		if ( $webhookUrl !== $webhookConfig->returnUrl || $hasMissingEvents ) {
			try {
				$this->webhooksRepository->updateWebhook( $this->merchantDetails->accessToken, $webhookConfig->id );

				$webhookConfig->returnUrl = $webhookUrl;
				$webhookConfig->events    = $registeredEvents;

				$this->webhooksRepository->saveWebhookConfig( $webhookConfig );
			} catch ( Exception $exception ) {
				Give_Admin_Settings::add_error( 'paypal-webhook-update-error', 'There was a problem updating your PayPal Donations webhook. Please disconnect your account and reconnect it.' );
			}
		}
	}
}
