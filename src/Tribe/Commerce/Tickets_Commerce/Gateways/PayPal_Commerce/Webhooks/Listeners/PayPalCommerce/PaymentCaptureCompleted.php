<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners\PayPalCommerce;

/**
 * Class PaymentCaptureCompleted
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Webhooks\Listeners\PayPalCommerce
 *
 */
class PaymentCaptureCompleted extends PaymentEventListener {

	/**
	 * @inheritDoc
	 */
	public function processEvent( $event ) {
		$payment = $this->paymentsRepository->getPaymentByPayment( $event->resource->id );

		// If there's no matching payment then it's not tracked by GiveWP
		if ( ! $payment ) {
			return;
		}

		// @todo Replace this.
		// Exit if payment status already set to publish.
		if ( ! give_update_payment_status( $payment->ID ) ) {
			return;
		}

		// @todo Replace this.
		give_insert_payment_note( $payment->ID, __( 'Charge Completed in PayPal', 'event-tickets' ) );

		// @todo Replace the action name.
		/**
		 * Fires when a charge has been completed via webhook
		 *
		 * @since TBD
		 */
		do_action( 'give_paypal_commerce_webhook_charge_completed', $event, $payment );
	}
}
