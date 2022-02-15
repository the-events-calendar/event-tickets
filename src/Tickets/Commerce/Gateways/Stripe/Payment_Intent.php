<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;

/**
 * Stripe orders aka Payment Intents class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Payment_Intent {

	/**
	 * Value to use when creating test payment intents.
	 *
	 * @since TBD
	 *
	 * @var float
	 */
	const TEST_VALUE = 1.28;

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

		$value = Value::create( static::TEST_VALUE );
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
				'tec-tc-stripe-test-payment-intent-failed',
				__( sprintf( 'Stripe reports that it is unable to process charges with the selected Payment Methods. Usually this means that one of the methods selected is not available or not configured in your Stripe account. The errors you see below were returned from Stripe, please correct any inconsistencies or contact Stripe support, then try again: <div class="stripe-errors">%s</div>', $compiled_errors ), 'event-tickets' )
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
	 * @param Value $value The value object to create a payment intent for.
	 * @param bool  $retry Is this a retry?
	 *
	 * @return mixed
	 */
	public static function create( Value $value, $retry = false ) {
		$fee = Application_Fee::calculate( $value );

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

	/**
	 * Creates a Payment Intent from cart.
	 *
	 * @since TBD
	 *
	 * @param Cart $cart
	 * @param bool $retry
	 *
	 * @return array
	 */
	public static function create_from_cart( Cart $cart, $retry = false ) {
		$items = tribe( Order::class )->prepare_cart_items_for_order( $cart );
		$value = tribe( Order::class )->get_value_total( array_filter( $items ) );

		return static::create( $value, $retry );
	}

	/**
	 * Calls the Stripe API and returns an existing Payment Intent.
	 *
	 * @since TBD
	 *
	 * @param string $payment_intent_id Payment Intent ID.
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

		$url = sprintf( '/payment_intents/%s', urlencode( $payment_intent_id ) );

		return Requests::get( $url, $query_args, $args );
	}

	/**
	 * Updates an existing payment intent to add any necessary data before confirming the purchase.
	 *
	 * @since TBD
	 *
	 * @param array $data The purchase data received from the front-end.
	 *
	 * @return array|\WP_Error|null
	 */
	public static function update( $payment_intent_id, $data ) {
		$query_args = [];
		$args       = [
			'body' => $data,
		];

		$url = sprintf( '/payment_intents/%s', urlencode( $payment_intent_id ) );

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
		$body       = [];
		$args       = [
			'body' => $body,
		];

		$url = sprintf( '/payment_intents/%s/cancel', urlencode( $payment_intent_id ) );

		Requests::post( $url, $query_args, $args );
	}

	/**
	 * Compile errors caught when creating a Payment Intent into a proper html notice for the admin.
	 *
	 * @since TBD
	 *
	 * @param array $errors List of errors returned from Stripe.
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
			$compiled .= sprintf( '<p><em>%s</em></p>', esc_html( $error[1] ) );
		}

		return $compiled;
	}

}