<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Repositories;

use Exception;
use InvalidArgumentException;

// @todo Implement PayPal Checkout SDK.
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Payments\CapturesRefundRequest;

use TEC\Tickets\Commerce\Gateways\PayPal\Models\Merchant_Detail;
use TEC\Tickets\Commerce\Gateways\PayPal\PayPal_Client;

/**
 * Class PayPal_Order
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Repositories
 *
 */
class PayPal_Order {

	/**
	 * @since 5.1.6
	 *
	 * @var PayPal_Client
	 */
	private $paypal_client;

	/**
	 * @since 5.1.6
	 *
	 * @var Merchant_Detail
	 */
	private $merchant_details;

	/**
	 * PayPal_Order constructor.
	 *
	 * @since 5.1.6
	 *
	 * @param Merchant_Detail $merchant_details
	 *
	 * @param PayPal_Client   $paypal_client
	 */
	public function __construct( PayPal_Client $paypal_client, Merchant_Detail $merchant_details ) {
		$this->paypal_client    = $paypal_client;
		$this->merchant_details = $merchant_details;
	}

	/**
	 * Approve order.
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param string $order_id
	 *
	 * @return string
	 */
	public function approve_order( $order_id ) {
		$request = new OrdersCaptureRequest( $order_id );

		try {
			return $this->paypal_client->get_http_client()->execute( $request )->result;
		} catch ( Exception $ex ) {
			// @todo Log the error.
			logError( 'Capture PayPal Commerce payment failure', sprintf( '<strong>Response</strong><pre>%1$s</pre>', print_r( json_decode( $ex->getMessage(), true ), true ) ) );

			throw $ex;
		}
	}

	/**
	 * Create order.
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param array $array
	 *
	 * @return string
	 */
	public function create_order( $array ) {
		$this->validate_create_order_arguments( $array );

		$request = new OrdersCreateRequest();
		// @todo Replace this with our bin code from Gateway::ATTRIBUTION_ID.
		$request->payPalPartnerAttributionId( Give( 'PAYPAL_COMMERCE_ATTRIBUTION_ID' ) );
		$request->body = [
			'intent'              => 'CAPTURE',
			'purchase_units'      => [
				[
					// @todo Replace this.
					'reference_id'        => get_post_field( 'post_name', $array['formId'] ),
					// @todo Replace this.
					'description'         => $array['formTitle'],
					'amount'              => [
						// @todo Replace this.
						'value'         => give_maybe_sanitize_amount( $array['paymentAmount'], [ 'currency' => give_get_currency( $array['formId'] ) ] ),
						// @todo Replace this.
						'currency_code' => give_get_currency( $array['formId'] ),
					],
					'payee'               => [
						'email_address' => $this->merchant_details->merchant_id,
						'merchant_id'   => $this->merchant_details->merchant_id_in_paypal,
					],
					'payer'               => [
						// @todo Replace these.
						'given_name'    => $array['payer']['firstName'],
						'surname'       => $array['payer']['lastName'],
						'email_address' => $array['payer']['email'],
					],
					'payment_instruction' => [
						'disbursement_mode' => 'INSTANT',
					],
				],
			],
			'application_context' => [
				'shipping_preference' => 'NO_SHIPPING',
				'user_action'         => 'PAY_NOW',
			],
		];

		try {
			return $this->paypal_client->get_http_client()->execute( $request )->result->id;
		} catch ( Exception $ex ) {
			logError( 'Create PayPal Commerce order failure', sprintf( '<strong>Request</strong><pre>%1$s</pre><br><strong>Response</strong><pre>%2$s</pre>', print_r( $request->body, true ), print_r( json_decode( $ex->getMessage(), true ), true ) ) );

			throw $ex;
		}
	}

	/**
	 * Refunds a processed payment
	 *
	 * @since 5.1.6
	 *
	 * @throws Exception
	 *
	 * @param $capture_id
	 *
	 * @return string The id of the refund
	 */
	public function refund_payment( $capture_id ) {
		$refund = new CapturesRefundRequest( $capture_id );

		try {
			return $this->paypal_client->get_http_client()->execute( $refund )->result->id;
		} catch ( Exception $exception ) {
			logError( 'Create PayPal Commerce payment refund failure', sprintf( '<strong>Response</strong><pre>%1$s</pre>', print_r( json_decode( $exception->getMessage(), true ), true ) ) );

			throw $exception;
		}
	}

	/**
	 * Validate argument given to create PayPal order.
	 *
	 * @since 5.1.6
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param array $array
	 *
	 */
	private function validate_create_order_arguments( $array ) {
		$required = [ 'formId', 'paymentAmount', 'payer' ];
		$array    = array_filter( $array ); // Remove empty values.

		if ( array_diff( $required, array_keys( $array ) ) ) {
			throw new InvalidArgumentException( __( 'To create a paypal order, please provide formId, paymentAmount and payer', 'event-tickets' ) );
		}
	}
}
