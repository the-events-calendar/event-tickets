<?php

namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Utils\Value;

class Payment_Intent {

	public static function test_payment_intent_creation( $payment_methods ) {

		$value = Value::create( 10 );
		$fee   = Application_Fee::calculate( $value );

		$query_args = [];
		$body       = [
			'currency'               => $value->get_currency_code(),
			'amount'                 => (string) $value->get_integer(),
			'payment_method_types'   => $payment_methods,
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

		$payment_intent = Requests::post( $url, $query_args, $args );

		if ( ! isset( $payment_intent['id'] ) && ! empty( $payment_intent['errors'] ) ) {
			$compiled_errors = static::compile_errors( $payment_intent );

			return new \WP_Error(
				'test-payment-intent-failed',
				__( sprintf( 'Your changes to payment methods accepted were not saved: It was not possible to create a Stripe PaymentIntent with the current configuration. The errors you see below were returned from Stripe, please check for any inconsistencies, or contact Stripe support to fix them and try again: %s', $compiled_errors ), 'event-tickets' )
			);
		}

		static::cancel_payment_intent( $payment_intent['id'] );


		return true;
	}

	public static function cancel_payment_intent( $payment_intent_id ) {
		$query_args = [];
		$body       = [
		];
		$args       = [
			'body' => $body,
		];

		$payment_intent_id = urlencode( $payment_intent_id );
		$url               = '/payment_intents/{payment_intent_id}/cancel';
		$url               = str_replace( '{payment_intent_id}', $payment_intent_id, $url );

		return Requests::post( $url, $query_args, $args );
	}

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