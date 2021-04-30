<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners;

/**
 * Class PaymentCaptureReversed
 *
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners
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
