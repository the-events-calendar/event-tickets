<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureReversed
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 * @since TBD
 */
class PaymentCaptureReversed extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $new_status = 'reversed';

	/**
	 * The event type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.REVERSED';
}
