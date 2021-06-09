<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureCompleted
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 */
class PaymentCaptureCompleted extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $new_status = 'completed';

	/**
	 * The event type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.COMPLETED';
}
