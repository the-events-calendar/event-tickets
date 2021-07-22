<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\REST;

use TEC\Tickets\Commerce\Gateways\PayPal\Repositories\_Order;

/**
 * Class Create_Order
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\REST
 */
class Create_Order {
	/**
	 * Create order.
	 *
	 * @todo  : handle payment create error on frontend.
	 *
	 * @since 5.1.6
	 */
	public function handle(  ) {
		$postData = give_clean( $_POST );
		$formId   = absint( tribe_get_request_var( 'give-form-id' ) );

		$data = [
			'formId'              => $formId,
			'formTitle'           => give_payment_gateway_item_title( [ 'post_data' => $postData ], 127 ),
			'paymentAmount'       => isset( $postData['give-amount'] ) ? (float) apply_filters( 'give_payment_total', give_maybe_sanitize_amount( $postData['give-amount'], [ 'currency' => give_get_currency( $formId ) ] ) ) : '0.00',
			'payer'               => [
				'firstName' => $postData['give_first'],
				'lastName'  => $postData['give_last'],
				'email'     => $postData['give_email'],
			],
			'application_context' => [
				'shipping_preference' => 'NO_SHIPPING',
			],
		];

		try {
			$result = tribe( _Order::class )->create_order( $data );

			wp_send_json_success(
				[
					'id' => $result,
				]
			);
		} catch ( \Exception $ex ) {
			wp_send_json_error(
				[
					'error' => json_decode( $ex->getMessage(), true ),
				]
			);
		}
	}
}