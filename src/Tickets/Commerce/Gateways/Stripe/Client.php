<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

/**
 * Class Client
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Client {

	/**
	 * The base url to call when making direct API calls
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $api_base_url = 'https://api.stripe.com/v1';

	/**
	 * The endpoint to use when managing payment intents
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $payment_intent_endpoint = '/payment_intents';

	/**
	 * Calls the Stripe API and returns a new PaymentIntent object, used to authenticate
	 * front-end payment requests.
	 *
	 * @since TBD
	 *
	 * @param string $currency 3-letter ISO code for the desired currency. Not all currencies are supported.
	 * @param int $value the payment value in the smallest currency unit (e.g: cents, if the purchase is in USD)
	 *
	 * @return array|\WP_Error
	 */
	public function create_payment_intent( $currency, $value ) {

		$response = wp_remote_post( $this->api_base_url . $this->payment_intent_endpoint, [
			'headers' => [
				'Authorization' => 'Bearer ' . tribe( Merchant::class )->get_client_secret(),
			],
			'body'    => [
				'currency' => $currency,
				'amount'   => $value,
			],
		] );

		return $response;
	}
}