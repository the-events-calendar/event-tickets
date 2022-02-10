<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;

/**
 * Stripe orders aka Payment Intents class.
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Payment_Intent {

	/**
	 * Create a simple payment intent with the designated payment methods to check for errors.
	 *
	 * If the payment intent succeeds it is cancelled. If it fails we display a notice to the user and not apply their
	 * settings.
	 *
	 * @since TBD
	 *
	 * @param array $payment_methods a list of payment_methods to allow in the Payment Intent.
	 *
	 * @return bool|\WP_Error
	 */
	public static function test_creation( $payment_methods ) {

		// Payment Intents for cards only are always valid.
		if ( 1 === count( $payment_methods ) && in_array( 'card', $payment_methods, true ) ) {
			return true;
		}

		$value = Value::create( 10 );
		$fee   = Application_Fee::calculate( $value );

		$query_args = [];
		$body       = [
			'currency'               => $value->get_currency_code(),
			'amount'                 => (string) $value->get_integer(),
			'payment_method_types'   => $payment_methods,
			'application_fee_amount' => (string) $fee->get_integer(),
		];

		$args = [
			'body' => $body,
		];

		$url = 'payment_intents';

		$payment_intent = Requests::post( $url, $query_args, $args );

		if ( ! isset( $payment_intent['id'] ) && ! empty( $payment_intent['errors'] ) ) {
			$compiled_errors = static::compile_errors( $payment_intent );

			return new \WP_Error(
				'test-payment-intent-failed',
				__( sprintf( 'Your changes to payment methods accepted were not saved: It was not possible to create a Stripe PaymentIntent with the current configuration. The errors you see below were returned from Stripe, please check for any inconsistencies, or contact Stripe support to fix them and try again: %s', $compiled_errors ), 'event-tickets' )
			);
		}

		static::cancel( $payment_intent['id'] );

		return true;
	}

	/**
	 * Calls the Stripe API and returns a new PaymentIntent object, used to authenticate
	 * front-end payment requests.
	 *
	 * @since TBD
	 *
	 * @param Value $value the value object to create a payment intent for.
	 * @param bool $retry is this a retry?
	 *
	 * @return mixed
	 */
	public static function create( Value $value, $retry = false ) {
		$fee   = Application_Fee::calculate( $value );

		$query_args = [];
		$body       = [
			'currency'               => $value->get_currency_code(),
			'amount'                 => (string) $value->get_integer(),
			'payment_method_types'   => tribe( Merchant::class )->get_payment_method_types( $retry ),
			'application_fee_amount' => (string) $fee->get_integer(),
		];

		$stripe_statement_descriptor = tribe_get_option( Settings::$option_statement_descriptor );

		if ( ! empty( $stripe_statement_descriptor ) ) {
			$body['statement_descriptor'] = substr( $stripe_statement_descriptor, 0, 22 );
		}

		$args = [
			'body' => $body,
		];

		$url = 'payment_intents';

		return Requests::post( $url, $query_args, $args );
	}

	public static function create_from_cart( Cart $cart, $retry = false ) {
		$items = tribe( Order::class )->prepare_cart_items_for_order( $cart );
		$value = tribe( Order::class )->get_value_total( array_filter( $items ) );

		return static::create( $value, $retry );
	}

	/**
	 * Calls the Stripe API and returns an existing Payment Intent based ona PI Client Secret.
	 *
	 * @since TBD
	 *
	 * @param string $payment_intent_id Payment Intent ID formatted from client `pi_*`
	 *
	 * @return array|\WP_Error
	 */
	public static function get( $payment_intent_id ) {
		$query_args = [];
		$body       = [
		];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent_id );
		$url               = '/payment_intents/{payment_intent_id}';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		return Requests::get( $url, $query_args, $args );
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
	public static function update( $payment_intent_id, $data ) {
		$payment_intent = static::get( $payment_intent_id );

		if ( empty( $payment_intent['id'] ) ) {
			// error
			return;
		}

		$data = wp_parse_args( $data, $payment_intent );
		$body = array_diff_assoc( $data, $payment_intent );

		if ( empty( $body ) ) {
			// noop
			return;
		}

		$query_args = [];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent['id'] );
		$url               = '/payment_intents/{payment_intent_id}';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		return Requests::post( $url, $query_args, $args );
	}

	/**
	 * Issue an API request to cancel a Payment Intent.
	 *
	 * @since TBD
	 *
	 * @param string $payment_intent_id the payment intent to cancel.
	 */
	public static function cancel( $payment_intent_id ) {
		$query_args = [];
		$body       = [
		];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent_id );
		$url               = '/payment_intents/{payment_intent_id}/cancel';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		Requests::post( $url, $query_args, $args );
	}

	/**
	 * Compile errors caught when creating a Payment Intent into a proper html notice for the admin.
	 *
	 * @since TBD
	 *
	 * @param array $errors list of errors returned from Stripe.
	 *
	 * @return string
	 */
	public static function compile_errors( $errors ) {
		$compiled = '';

		if ( empty( $errors['errors'] ) ) {
			return $compiled;
		}

		if ( ! is_array( $errors['errors'] ) ) {
			return $errors['errors'];
		}

		foreach ( $errors['errors'] as $error ) {
			$compiled .= sprintf( '<div>%s<p>%s</p></div>', $error[0], $error[1] );
		}

		return $compiled;
	}

}