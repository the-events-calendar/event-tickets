<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureRefunded
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 * @since 5.1.6
 */
class PaymentCaptureRefunded extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $new_status = 'refunded';

	/**
	 * The event type.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.REFUNDED';
}
