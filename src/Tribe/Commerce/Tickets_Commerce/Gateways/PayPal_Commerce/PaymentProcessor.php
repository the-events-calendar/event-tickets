<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce;

use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models\PayPalOrder;
use Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\PayPalClient;
use PayPalCheckoutSdk\Orders\OrdersGetRequest;

/**
 * Class PaymentProcessor
 *
 * @since TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 */
class PaymentProcessor {

	/**
	 * @var array
	 */
	private $paymentFormData;

	/**
	 * Handle payment form submission.
	 *
	 * @since TBD
	 *
	 * @param array $paymentFormData
	 *
	 */
	public function handle( $paymentFormData ) {
		$this->paymentFormData = (array) $paymentFormData;

		if ( ! $this->isOneTimePayment() ) {
			return;
		}

		$formId = absint( $this->paymentFormData['post_data']['give-form-id'] );

		$paymentData = [
			'price'           => $this->paymentFormData['price'],
			'give_form_title' => $this->paymentFormData['post_data']['give-form-title'],
			'give_form_id'    => $formId,
			'give_price_id'   => isset( $this->paymentFormData['post_data']['give-price-id'] ) ? $this->paymentFormData['post_data']['give-price-id'] : '',
			'date'            => $this->paymentFormData['date'],
			'user_email'      => $this->paymentFormData['user_email'],
			'purchase_key'    => $this->paymentFormData['purchase_key'],
			'currency'        => give_get_currency(),
			'user_info'       => $this->paymentFormData['user_info'],
			'status'          => 'pending',
			'gateway'         => $this->paymentFormData['gateway'],
		];

		$paymentId = give_insert_payment( $paymentData );

		if ( ! $paymentId ) {
			$this->redirectBackToPaymentForm();
		}

		$this->redirectDonorToSuccessPage( $paymentId );

		exit();
	}

	/**
	 * Return back to payment form page after logging error.
	 *
	 * @since TBD
	 */
	private function redirectBackToPaymentForm() {
		// Record the error.
		// @todo Replace this with a logging function.
		give_record_gateway_error(
			esc_html__( 'Payment Error', 'event-tickets' ),
			sprintf(
				/* translators: %s: payment data */
				esc_html__( 'The payment creation failed before processing the PayPalCommerce gateway request. Payment data: %s', 'event-tickets' ),
				print_r( $this->paymentFormData, true )
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
	 * @param int $paymentId
	 */
	private function redirectDonorToSuccessPage( $paymentId ) {
		$orderDetailRequest = new OrdersGetRequest( $this->paymentFormData['post_data']['payPalOrderId'] );

		$client = tribe( PayPalClient::class )->getHttpClient();

		$orderDetails = (array) $client->execute( $orderDetailRequest )->result;

		$order = PayPalOrder::fromArray( $orderDetails );

		give_insert_payment_note(
			$paymentId,
			// @todo Add translator text
			sprintf(
				__( 'Transaction Successful. PayPal Transaction ID: %1$s    PayPal Order ID: %2$s', 'event-tickets' ),
				$order->payment->id,
				$order->id
			)
		);

		give_set_payment_transaction_id( $paymentId, $order->payment->id );

		Give( 'payment_meta' )->update_meta( $paymentId, '_give_order_id', $order->id );

		// Do not need to set payment to complete if already completed by PayPal webhook.
		if ( 'COMPLETED' === $order->payment->status ) {
			give_update_payment_status( $paymentId );
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
	 * Return whether or not payment is onetime.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	private function isOneTimePayment() {
		return array_key_exists( 'post_data', $this->paymentFormData ) && array_key_exists( 'payPalOrderId', $this->paymentFormData['post_data'] );
	}
}
