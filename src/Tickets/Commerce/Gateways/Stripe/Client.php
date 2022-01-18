<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

class Client {

	public $api_base_url = 'https://api.stripe.com/v1';

	public $payment_intent_endpoint = '/payment_intents';

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