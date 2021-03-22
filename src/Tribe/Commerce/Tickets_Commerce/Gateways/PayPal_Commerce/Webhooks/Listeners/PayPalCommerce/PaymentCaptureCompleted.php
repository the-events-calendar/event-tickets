<?php

namespace TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\PayPalCommerce;

/**
 * Class PaymentCaptureCompleted
 *
 * @since   TBD
 * @package TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\PayPalCommerce
 *
 */
class PaymentCaptureCompleted extends PaymentEventListener {

	/**
	 * @inheritDoc
	 */
	public function processEvent( $event ) {
		$donation = $this->paymentsRepository->getDonationByPayment( $event->resource->id );

		// If there's no matching donation then it's not tracked by GiveWP
		if ( ! $donation ) {
			return;
		}

		// Exit if donation status already set to publish.
		if ( ! give_update_payment_status( $donation->ID, 'publish' ) ) {
			return;
		}

		give_insert_payment_note( $donation->ID, __( 'Charge Completed in PayPal', 'event-tickets' ) );

		/**
		 * Fires when a charge has been completed via webhook
		 *
		 * @since TBD
		 */
		do_action( 'give_paypal_commerce_webhook_charge_completed', $event, $donation );
	}
}
