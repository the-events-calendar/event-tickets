<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Utils__Array as Arr;

/**
 * Class Client
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Client {

	/**
	 * Base string to use when composing payment intent transient names
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $payment_intent_transient_prefix = 'paymentintent-';

	public $payment_intent_transient_name;

	/**
	 * Get environment base URL.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_environment_url() {
		$merchant = tribe( Merchant::class );

		// @todo determine if the sandbox of stripe has a diff URL.
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
	public function create_payment_intent() {

		$this->set_payment_intent_transient_name();

		$cart = tribe( Cart::class );

		$items = $cart->get_items_in_cart();

		$items = array_map(
			static function ( $item ) {
				/** @var Value $ticket_value */
				$ticket_value = tribe( Ticket::class )->get_price_value( $item['ticket_id'] );

				if ( null === $ticket_value ) {
					return null;
				}

				$item['price']     = $ticket_value->get_decimal();
				$item['sub_total'] = $ticket_value->sub_total( $item['quantity'] )->get_decimal();

				return $item;
			},
			$items
		);
		$value = tribe( Order::class )->get_value_total( array_filter( $items ) );

		$query_args = [];
		$body       = [
			'currency'               => $value->get_currency_code(),
			'amount'                 => $value->get_integer(),
			'payment_method_types'   => tribe_get_option( Settings::$option_checkout_element_payment_methods ),
			'application_fee_amount' => 0,
		];

		$stripe_statement_descriptor = tribe_get_option( Settings::$option_statement_descriptor );

		if ( empty( $stripe_statement_descriptor ) ) {
			$body['statement_descriptor'] = substr( $stripe_statement_descriptor, 0, 22 );
		}

		$args = [
			'body' => $body,
		];

		$url = 'payment_intents';

		$payment_intent = $this->post( $url, $query_args, $args );

		$this->store_payment_intent( $payment_intent );
	}

	/**
	 * Updates an existing payment intent to add any necessary data before confirming the purchase.
	 *
	 * @since TBD
	 *
	 * @param array $data the purchase data received from the front-end
	 *
	 * @return array|\WP_Error|null
	 */
	public function update_payment_intent( $data ) {

		$payment_intent = $this->get_payment_intent( $data['payment_intent']['id'] );

		$stripe_receipt_emails = tribe_get_option( Settings::$option_stripe_receipt_emails );

		// Currently this method is only used to add an email recipient for Stripe receipts. If this is not
		// required, only return the payment intent object to store.
		if ( ! $stripe_receipt_emails ) {
			return;
		}

		if ( $stripe_receipt_emails && ! empty( $data['billing_details']['email'] ) ) {
			$body['receipt_email'] = $data['billing_details']['email'];
		}

		$query_args = [];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent['id'] );
		$url               = '/payment_intents/{payment_intent_id}';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		return $this->post( $url, $query_args, $args );
	}

	/**
	 * Assembles basic data about the payment intent created at page-load to use in javascript
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function get_publishable_payment_intent_data() {
		$pi = $this->get_payment_intent_transient();

		if ( empty( $pi ) ) {
			return [];
		}

		return [
			'id'   => $pi['id'],
			'key'  => $pi['client_secret'],
			'name' => $this->get_payment_intent_transient_name(),
		];
	}

	/**
	 * Compose the transient name used for payment intent transients
	 *
	 * @since TBD
	 */
	public function set_payment_intent_transient_name() {
		$this->payment_intent_transient_name = $this->payment_intent_transient_prefix . md5( tribe( Cart::class )->get_cart_hash() );
	}

	/**
	 * Returns the transient name used for payment intent transients
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_payment_intent_transient_name() {

		if ( empty( $this->payment_intent_transient_name ) ) {
			$this->set_payment_intent_transient_name();
		}

		return $this->payment_intent_transient_name;
	}

	/**
	 * Retrieve a stored payment intent referring to the current cart
	 *
	 * @since TBD
	 *
	 * @return array|false
	 */
	public function get_payment_intent_transient() {
		return get_transient( $this->get_payment_intent_transient_name() );
	}

	/**
	 * Store a payment intent array in a transient
	 *
	 * @since TBD
	 *
	 * @param array $payment_intent
	 */
	public function store_payment_intent( $payment_intent ) {
		if ( ! empty( $payment_intent['client_secret'] ) ) {
			set_transient( $this->get_payment_intent_transient_name(), $payment_intent, 6 * HOUR_IN_SECONDS );
		}
	}

	/**
	 * Calls the Stripe API and returns an existing Payment Intent based ona PI Client Secret.
	 *
	 * @since TBD
	 *
	 * @param string $payment_intent_id Payment Intent ID formatted from client `pi_*`
	 * @param string $client_secret     Client Secret formatted from client `pi_*`
	 *
	 * @return array|\WP_Error
	 */
	public function get_payment_intent( $payment_intent_id ) {
		$query_args = [];
		$body       = [
		];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent_id );
		$url               = '/payment_intents/{payment_intent_id}';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		return $this->get( $url, $query_args, $args );
	}

	public function check_account_status( $client_data ) {
		$return = [
			'connected'       => false,
			'charges_enabled' => false,
			'errors'          => [],
			'capabilities'    => [],
		];

		if ( empty( $client_data['client_id'] )
			 || empty( $client_data['client_secret'] )
			 || empty( $client_data['publishable_key'] )
		) {
			return $return;
		}

		$account_id = urlencode( $client_data['client_id'] );
		$url        = '/accounts/{account_id}';
		$url        = str_replace( '{account_id}', $account_id, $url );

		$response = $this->get( $url, [], [] );

		if ( ! empty( $response['object'] ) && 'account' === $response['object'] ) {
			$return['connected'] = true;

			if ( $response['charges_enabled'] ) {
				$return['charges_enabled'] = true;
			}

			if ( ! empty( $response['capabilities'] ) ) {
				$return['capabilities'] = $response['capabilities'];
			}

			if ( ! empty( $response['requirements']['errors'] ) ) {
				$return['errors']['requirements'] = $response['requirements']['errors'];
			}

			if ( ! empty( $response['future_requirements']['errors'] ) ) {
				$return['errors']['future_requirements'] = $response['future_requirements']['errors'];
			}
		}

		if ( ! empty( $response['type'] ) && in_array( $response['type'], [
				'api_error',
				'card_error',
				'idempotency_error',
				'invalid_request_error',
			], true ) ) {

			$return['request_error'] = $response;
		}

		return $return;
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
				'Authorization' => 'Bearer ' . tribe( Merchant::class )->get_client_secret(),
			],
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