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
	 * Get environment base URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_environment_url() {
		$merchant = tribe( Merchant::class );

		return $merchant->is_sandbox() ?
			'https://api.stripe.com/v1' :
			'https://api.stripe.com/v1';
	}

	/**
	 * Get REST API endpoint URL for requests.
	 *
	 * @since TBD
	 *
	 * @param string $endpoint   The endpoint path.
	 * @param array  $query_args Query args appended to the URL.
	 *
	 * @return string The API URL.
	 *
	 */
	public function get_api_url( $endpoint, array $query_args = [] ) {
		$base_url = $this->get_environment_url();
		$endpoint = ltrim( $endpoint, '/' );

		return add_query_arg( $query_args, "{$base_url}/{$endpoint}" );
	}

	/**
	 * Calls the Stripe API and returns a new PaymentIntent object, used to authenticate
	 * front-end payment requests.
	 *
	 * @since TBD
	 *
	 * @param string $currency 3-letter ISO code for the desired currency. Not all currencies are supported.
	 * @param int    $value    the payment value in the smallest currency unit (e.g: cents, if the purchase is in USD)
	 *
	 * @return array|\WP_Error
	 */
	public function create_payment_intent( $currency, $value ) {
		$query_args = [];
		$body       = [
			'currency' => $currency,
			'amount'   => $value,
		];
		$args       = [
			'body' => $body,
		];

		$url = 'payment_intents';

		return $this->post( $url, $query_args, $args );
	}


	/**
	 * Send a given method request to a given URL in the Stripe API.
	 *
	 * @todo  For later we need to build a Contract for Requests in general.
	 *
	 * @since TBD
	 *
	 * @param string $method
	 * @param string $url
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 * @param int    $retries Param used to determine the amount of time this particular request was retried.
	 *
	 * @return array|\WP_Error
	 */
	public function request( $method, $url, array $query_args = [], array $request_arguments = [], $raw = false, $retries = 0 ) {
		$method = strtoupper( $method );

		// If the endpoint passed is a full URL don't try to append anything.
		$url = 0 !== strpos( $url, 'https://' )
			? $this->get_api_url( $url, $query_args )
			: add_query_arg( $query_args, $url );

		$default_arguments = [
			'headers' => [
				'Accept'        => 'application/json',
				'Authorization' => 'Bearer ' . tribe( Merchant::class )->get_client_secret(),
				'Content-Type'  => 'application/json',
			]
		];

		// By default, it's important that we have a body set for any method that is not the GET method.
		if ( 'GET' !== $method ) {
			$default_arguments['body'] = [];
		}

		foreach ( $default_arguments as $key => $default_argument ) {
			$request_arguments[ $key ] = array_merge( $default_argument, Arr::get( $request_arguments, $key, [] ) );
		}

		if ( 'GET' !== $method ) {
			$content_type = Arr::get( $request_arguments, [ 'headers', 'Content-Type' ] );
			if ( empty( $content_type ) ) {
				$content_type = Arr::get( $request_arguments, [ 'headers', 'content-type' ] );
			}

			// For all other methods we try to make the body into the correct type.
			if (
				! empty( $request_arguments['body'] )
				&& 'application/json' === strtolower( $content_type )
			) {
				$request_arguments['body'] = wp_json_encode( $request_arguments[ $key ] );
			}
		}

		if ( 'GET' === $method ) {
			$response = wp_remote_get( $url, $request_arguments );
		} elseif ( 'POST' === $method ) {
			$response = wp_remote_post( $url, $request_arguments );
		} else {
			$request_arguments['method'] = $method;
			$response                    = wp_remote_request( $url, $request_arguments );
		}

		if ( is_wp_error( $response ) ) {
			tribe( 'logger' )->log_error( sprintf(
				'[%s] Stripe "%s" request error: %s',
				$method,
				$url,
				$response->get_error_message()
			), 'tickets-commerce' );

			return $response;
		}

		// When raw is true means we dont do any logic.
		if ( true === $raw ) {
			return $response;
		}

		/**
		 * @todo Determine if Stripe might need a retry pattern like PayPal.
		 */

		/**
		 * @todo we need to log and be more verbose about the responses. Specially around failed JSON strings.
		 */
		$response_body = wp_remote_retrieve_body( $response );
		$response_body = @json_decode( $response_body, true );
		if ( empty( $response_body ) ) {
			return $response;
		}

		if ( ! is_array( $response_body ) ) {
			tribe( 'logger' )->log_error( sprintf( '[%s] Unexpected Stripe %s response', $url, $method ), 'tickets-commerce' );

			return new \WP_Error( 'tec-tickets-commerce-gateway-stripe-client-unexpected', null, [
				'method'            => $method,
				'url'               => $url,
				'query_args'        => $query_args,
				'request_arguments' => $request_arguments,
				'response'          => $response,
			] );
		}

		return $response_body;
	}

	/**
	 * Send a GET request to the Stripe API.
	 *
	 * @todo  For later we need to build a Contract for Requests in general.
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public function get( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return $this->request( 'GET', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * Send a POST request to the Stripe API.
	 *
	 * @todo  For later we need to build a Contract for Requests in general.
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public function post( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return $this->request( 'POST', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * Send a PATCH request to the Stripe API.
	 *
	 * @todo  For later we need to build a Contract for Requests in general.
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public function patch( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return $this->request( 'PATCH', $endpoint, $query_args, $request_arguments, $raw );
	}

	/**
	 * Send a DELETE request to the Stripe API.
	 *
	 * @todo  For later we need to build a Contract for Requests in general.
	 * @since TBD
	 *
	 * @param string $endpoint
	 * @param array  $query_args
	 * @param array  $request_arguments
	 * @param bool   $raw
	 *
	 * @return array|null
	 */
	public function delete( $endpoint, array $query_args = [], array $request_arguments = [], $raw = false ) {
		return $this->request( 'DELETE', $endpoint, $query_args, $request_arguments, $raw );
	}

}