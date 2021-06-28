<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners;

/**
 * Class PaymentCaptureDenied
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks\Listeners
 *
 */
class PaymentCaptureDenied extends PaymentEventListener {
	/**
	 * The new status to set with successful event.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $new_status = 'denied';

	/**
	 * The event type.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected $event_type = 'PAYMENT.CAPTURE.DENIED';
}
