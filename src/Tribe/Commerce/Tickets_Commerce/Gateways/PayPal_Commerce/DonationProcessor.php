<?php

namespace TEC\PaymentGateways\PayPalCommerce;

use TEC\PaymentGateways\PayPalCommerce\SDK\Models\PayPalOrder;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

/**
 * Class DonationProcessor
 *
 * @since TBD
 * @package TEC\PaymentGateways\PayPalCommerce
 *
 */
class DonationProcessor {

	/**
	 * @var array
	 */
	private $donationFormData;

	/**
	 * Handle donation form submission.
	 *
	 * @since TBD
	 *
	 * @param array $donationFormData
	 *
	 */
	public function handle( $donationFormData ) {
		$this->donationFormData = (array) $donationFormData;

		if ( ! $this->isOneTimeDonation() ) {
			return;
		}

		$formId = absint( $this->donationFormData['post_data']['give-form-id'] );

		$donationData = [
			'price'           => $this->donationFormData['price'],
			'give_form_title' => $this->donationFormData['post_data']['give-form-title'],
			'give_form_id'    => $formId,
			'give_price_id'   => isset( $this->donationFormData['post_data']['give-price-id'] ) ? $this->donationFormData['post_data']['give-price-id'] : '',
			'date'            => $this->donationFormData['date'],
			'user_email'      => $this->donationFormData['user_email'],
			'purchase_key'    => $this->donationFormData['purchase_key'],
			'currency'        => give_get_currency(),
			'user_info'       => $this->donationFormData['user_info'],
			'status'          => 'pending',
			'gateway'         => $this->donationFormData['gateway'],
		];

		$donationId = give_insert_payment( $donationData );

		if ( ! $donationId ) {
			$this->redirectBackToDonationForm();
		}

		$this->redirectDonorToSuccessPage( $donationId );

		exit();
	}

	/**
	 * Return back to donation form page after logging error.
	 *
	 * @since TBD
	 */
	private function redirectBackToDonationForm() {
		// Record the error.
		// @todo Replace this with a logging function.
		give_record_gateway_error(
			esc_html__( 'Payment Error', 'event-tickets' ),
			sprintf(
				/* translators: %s: payment data */
				esc_html__( 'The payment creation failed before processing the PayPalCommerce gateway request. Payment data: %s', 'event-tickets' ),
				print_r( $this->donationFormData, true )
			)
		);

		// @todo Replace this with an error+notice follow-up function.
		give_set_error( 'event-tickets', esc_html__( 'An error occurred while processing your payment. Please try again.', 'event-tickets' ) );

		// Problems? Send back.
		// @todo Replace this.
		give_send_back_to_checkout();
	}

	/**
	 * Redirect donor to success page.
	 *
	 * @since TBD
	 *
	 * @param int $donationId
	 */
	private function redirectDonorToSuccessPage( $donationId ) {
		$orderDetailRequest = new OrdersGetRequest( $this->donationFormData['post_data']['payPalOrderId'] );

		$client = tribe( PayPalClient::class )->getHttpClient();

		$orderDetails = (array) $client->execute( $orderDetailRequest )->result;

		$order = PayPalOrder::fromArray( $orderDetails );

		give_insert_payment_note(
			$donationId,
			// @todo Add translator text
			sprintf(
				__( 'Transaction Successful. PayPal Transaction ID: %1$s    PayPal Order ID: %2$s', 'event-tickets' ),
				$order->payment->id,
				$order->id
			)
		);

		give_set_payment_transaction_id( $donationId, $order->payment->id );

		Give( 'payment_meta' )->update_meta( $donationId, '_give_order_id', $order->id );

		// Do not need to set donation to complete if already completed by PayPal webhook.
		if ( 'COMPLETED' === $order->payment->status ) {
			give_update_payment_status( $donationId );
		}

		wp_safe_redirect(
			add_query_arg(
				[
					'payment-confirmation' => 'paypal-commerce',
				],
				give_get_success_page_url()
			)
		);

		exit();
	}

	/**
	 * Return whether or not donation is onetime.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function isOneTimeDonation() {
		return array_key_exists( 'post_data', $this->donationFormData ) && array_key_exists( 'payPalOrderId', $this->donationFormData['post_data'] );
	}
}
