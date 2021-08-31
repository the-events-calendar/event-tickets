<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureCompleted
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 */
class Payment_Capture_Completed extends Payment_Event_Listener {
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
