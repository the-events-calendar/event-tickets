<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureReversed
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 * @since 5.1.6
 */
class PaymentCaptureReversed extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $new_status = 'reversed';

	/**
	 * The event type.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.REVERSED';
}
