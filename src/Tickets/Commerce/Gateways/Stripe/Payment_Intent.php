<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use RuntimeException;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Commerce\Gateways\Stripe\Settings as Stripe_Settings;

/**
 * Stripe orders aka Payment Intents class.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class Payment_Intent {

	/**
	 * The minimum amount to charge for the current currency is multiplied by this value to produce the test creation amount.
	 *
	 * @since 5.3.0
	 *
	 * @var float
	 */
	const TEST_MULTIPLIER = 3.14;

	/**
	 * The key used to identify payment intents created to validate configurations.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $test_metadata_key = 'payment_intent_validation_test';

	/**
	 * The key used to identify payment intents created in Tickets Commerce.
	 *
	 * @since 5.3.2
	 *
	 * @var string
	 */
	public static $tc_metadata_identifier = 'tec_tc_payment_intent';

	/**
	 * Create a simple payment intent with the designated payment methods to check for errors.
	 *
	 * If the payment intent succeeds it is cancelled. If it fails we display a notice to the user and not apply their
	 * settings.
	 *
	 * @since 5.3.0
	 *
	 * @param array $payment_methods a list of payment_methods to allow in the Payment Intent.
	 *
	 * @return bool|\WP_Error
	 */
	public static function test_creation( $payment_methods, $retry = false ) {
		// Payment Intents for cards only are always valid.
		if ( 1 === count( $payment_methods ) && in_array( 'card', $payment_methods, true ) && ! defined( 'DOING_AJAX' ) ) {
			return true;
		}

		$value = Value::create( static::get_charge_amount() );
		$fee   = Application_Fee::calculate( $value );

		$query_args = [];
		$body       = [
			'currency'               => $value->get_currency_code(),
			'amount'                 => (string) $value->get_integer(),
			'payment_method_types'   => $payment_methods,
			'application_fee_amount' => (string) $fee->get_integer(),
			'metadata'               => [
				static::$test_metadata_key      => true,
				static::$tc_metadata_identifier => true,
			],
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
				// Translators: %s is the html-formatted response from Stripe servers containing the error messages.
				sprintf( __( 'Stripe reports that it is unable to process charges with the selected Payment Methods. Usually this means that one of the methods selected is not available or not configured in your Stripe account. The errors you see below were returned from Stripe, please correct any inconsistencies or contact Stripe support, then try again: <div class="stripe-errors">%s</div>', 'event-tickets' ), $compiled_errors )
			);
		}

		static::cancel( $payment_intent['id'] );

		return true;
	}

	/**
	 * Calls the Stripe API and returns a new PaymentIntent object, used to authenticate
	 * front-end payment requests.
	 *
	 * @since 5.3.0
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
			'metadata'               => [
				static::$tc_metadata_identifier => true,
			],
		];

		$stripe_statement_descriptor = tribe_get_option( Stripe_Settings::$option_statement_descriptor );

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
	 * @since 5.3.0
	 *
	 * @param Cart $cart
	 * @param bool $retry
	 *
	 * @return array
	 */
	public static function create_from_cart( Cart $cart, $retry = false ) {
		$items = tribe( Order::class )->prepare_cart_items_for_order( $cart );
		if ( empty( $items ) ) {
			return [];
		}

		$value = tribe( Order::class )->get_value_total( array_filter( $items ) );

		/**
		 * Filters the value and items before creating a Payment Intent.
		 *
		 * @since 5.18.0
		 *
		 * @param Value $value The total value of the cart.
		 * @param array $items The items in the cart
		 */
		$value = apply_filters( 'tec_tickets_commerce_stripe_create_from_cart', $value, $items );

		if ( ! $value instanceof Value && is_numeric( $value ) ) {
			$value = Value::create( $value );
		}

		// Ensure we have a Value object returned from the filters.
		if ( ! $value instanceof Value ) {
			throw new RuntimeException( esc_html__( 'Value object not returned from filter', 'event-tickets' ) );
		}

		return static::create( $value, $retry );
	}

	/**
	 * Calls the Stripe API and returns an existing Payment Intent.
	 *
	 * @since 5.3.0
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
	 * @since 5.3.0
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
	 * @since 5.3.0
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
	 * @since 5.3.0
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

	/**
	 * Calculates the minimum charge amount allowed for the current currency
	 *
	 * @since 5.3.0
	 *
	 * @return int|float|null returns an int or float with the minimum value allowed, null if Stripe does not support
	 *                        the currency.
	 */
	public static function get_charge_amount() {
		$currency     = Currency::get_currency_code();
		$currency_map = Currency::get_default_currency_map();

		return static::TEST_MULTIPLIER * $currency_map[ $currency ]['stripe_minimum_charge'];
	}

	/**
	 * Intercept saving settings to check if any new payment methods would break Stripe payment intents.
	 *
	 * @since 5.3.0
	 *
	 * @param mixed  $value    The new value.
	 * @param string $field_id The field id in the options.
	 *
	 * @return array
	 */
	public static function validate_payment_methods( $value, $field_id ) {

		if ( ! tribe( Merchant::class )->is_connected() ) {
			return $value;
		}

		if ( ! isset( $_POST['tribeSaveSettings'] ) || ! isset( $_POST['current-settings-tab'] ) ) {
			return $value;
		}

		$checkout_type   = tribe_get_request_var( Stripe_Settings::$option_checkout_element );
		$payment_methods = tribe_get_request_var( $field_id );
		$current_methods = tribe_get_option( $field_id, [] );

		if ( empty( $payment_methods ) ) {
			if ( $checkout_type === Stripe_Settings::PAYMENT_ELEMENT_SLUG ) {
				tribe( 'settings' )->errors[] = esc_html__( 'Payment methods accepted cannot be empty', 'event-tickets' );
			}

			// Revert value to the previous configuration.
			return $current_methods;
		}

		sort( $payment_methods );
		sort( $current_methods );

		// If the two arrays are equal, there's no need to create a new test.
		if ( $payment_methods === $current_methods ) {
			return $current_methods;
		}

		$payment_intent_test = static::test_creation( $payment_methods );

		if ( ! is_wp_error( $payment_intent_test ) ) {
			// Payment Settings are working, great!
			return $value;
		}

		// Payment attempt failed. Provide an alert in the Dashboard.
		tribe( 'settings' )->errors[] = $payment_intent_test->get_error_message();

		// Revert value to the previous configuration.
		return tribe_get_option( $field_id, [] );
	}
}
