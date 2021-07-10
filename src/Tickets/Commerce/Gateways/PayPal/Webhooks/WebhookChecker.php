<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use Exception;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models\MerchantDetail;
use TEC\Tickets\Commerce\Gateways\PayPal\SDK\Repositories\Webhooks;
use TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\WebhooksRoute;

class WebhookChecker {

	/**
	 * @since 5.1.6
	 *
	 * @var Webhooks
	 */
	private $webhooksRepository;

	/**
	 * @since 5.1.6
	 *
	 * @var WebhooksRoute
	 */
	private $webhooksRoute;

	/**
	 * @since 5.1.6
	 *
	 * @var WebhookRegister
	 */
	private $webhookRegister;

	/**
	 * @since 5.1.6
	 *
	 * @var MerchantDetail
	 */
	private $merchantDetails;

	/**
	 * WebhookChecker constructor.
	 *
	 * @since 5.1.6
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
	 * @since 5.1.6
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

		$missingEvents    = array_merge(
			array_diff( $registeredEvents, $webhookConfig->events ),
			array_diff( $webhookConfig->events, $registeredEvents )
		);
		$hasMissingEvents = ! empty( $missingEvents );

		// Update the webhook if the return url or events have changed
		if ( $webhookUrl !== $webhookConfig->returnUrl || $hasMissingEvents ) {
			try {
				$this->webhooksRepository->updateWebhook( $this->merchantDetails->accessToken, $webhookConfig->id );

				$webhookConfig->returnUrl = $webhookUrl;
				$webhookConfig->events    = $registeredEvents;

				$this->webhooksRepository->saveWebhookConfig( $webhookConfig );
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
