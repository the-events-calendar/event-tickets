<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureRefunded
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 * @since TBD
 */
class PaymentCaptureRefunded extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $new_status = 'refunded';

	/**
	 * The event type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.REFUNDED';
}
