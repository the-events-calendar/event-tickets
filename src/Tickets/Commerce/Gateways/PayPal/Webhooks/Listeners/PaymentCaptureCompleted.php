<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureCompleted
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 */
class PaymentCaptureCompleted extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $new_status = 'completed';

	/**
	 * The event type.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.COMPLETED';
}
