<?php

namespace TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\PayPalCommerce;

/**
 * Class PaymentCaptureRefunded
 *
 * @package TEC\PaymentGateways\PayPalCommerce\Webhooks\Listeners\PayPalCommerce
 *
 * @since TBD
 */
class PaymentCaptureRefunded extends PaymentEventListener {

	/**
	 * @inheritDoc
	 */
	public function processEvent( $event ) {
		$paymentId = $this->getPaymentFromRefund( $event->resource );

		$donation = $this->paymentsRepository->getDonationByPayment( $paymentId );

		// If there's no matching donation then it's not tracked by GiveWP
		if ( ! $donation ) {
			return;
		}

		// @todo Replace this.
		// Exit if donation status already set to refunded.
		if ( ! give_update_payment_status( $donation->ID, 'refunded' ) ) {
			return;
		}

		// @todo Replace this.
		give_insert_payment_note( $donation->ID, __( 'Charge refunded in PayPal', 'event-tickets' ) );

		// @todo Replace the action name.
		/**
		 * Fires when a charge has been refunded via webhook
		 *
		 * @since TBD
		 */
		do_action( 'give_paypal_commerce_webhook_charge_refunded', $event, $donation );
	}

	/**
	 * This uses the links property of the refund to retrieve the refunded Payment from PayPal
	 *
	 * @since TBD
	 *
	 * @param object $refund
	 *
	 * @return string
	 */
	private function getPaymentFromRefund( $refund ) {
		$link = current( array_filter( $refund->links, static function ( $link ) {
			return $link->rel === 'up';
		} ) );

		$accountDetails = $this->merchantDetails->getDetails();

		$response = wp_remote_request( $link->href, [
			'method'  => $link->method,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Authorization' => "Bearer $accountDetails->accessToken",
			],
		] );

		$response = json_decode( $response['body'], false );

		return $response->id;
	}
}
