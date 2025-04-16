<?php
/**
 * Square Payment Processing Class
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use RuntimeException;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;
use WP_Error;
use WP_Post;
/**
 * Square payment processing class.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Payment {

	/**
	 * The key used to identify Square payments created in Tickets Commerce.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static string $tc_metadata_identifier = 'tec_tc_square_payment';

	/**
	 * Create a payment from the provided Value object.
	 *
	 * @since TBD
	 *
	 * @param string   $source_id The source ID.
	 * @param Value    $value     The value object to create a payment for.
	 * @param ?WP_Post $order     The order post object.
	 *
	 * @return ?array| The payment data.
	 */
	public static function create( string $source_id, Value $value, ?WP_Post $order = null ): ?array { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
		$merchant = tribe( Merchant::class );

		if ( ! $merchant->is_active() ) {
			return null;
		}

		$query_args = [];
		$body       = [
			'amount_money'    => [
				'amount'   => (int) $value->get_integer(),
				'currency' => $value->get_currency_code(),
			],
			'idempotency_key' => uniqid( 'tec-square-', true ),
			'source_id'       => $source_id,
			'location_id'     => $merchant->get_location_id(),
			'metadata'        => [
				static::$tc_metadata_identifier => true,
			],
		];

		if ( $order instanceof WP_Post ) {
			$body['reference_id'] = $order->ID;
		}

		$args = [
			'body'    => $body,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		return tribe( Requests::class )->post( 'payments', $query_args, $args );
	}

	/**
	 * Creates a payment from cart.
	 *
	 * @since TBD
	 *
	 * @param string $source_id The source ID.
	 * @param Cart   $cart      The cart object.
	 * @param bool   $retry     Whether this is a retry attempt.
	 *
	 * @return array|WP_Error The payment data or WP_Error on failure.
	 *
	 * @throws RuntimeException If the value object is not returned from the filter.
	 */
	public static function create_from_cart( string $source_id, Cart $cart, ?WP_Post $order = null ) {
		$items = tribe( Order::class )->prepare_cart_items_for_order( $cart );
		if ( empty( $items ) ) {
			return [];
		}

		$value = tribe( Order::class )->get_value_total( array_filter( $items ) );

		/**
		 * Filters the value and items before creating a Square payment.
		 *
		 * @since TBD
		 *
		 * @param Value  $value     The total value of the cart.
		 * @param array  $items     The items in the cart.
		 * @param string $source_id The source ID.
		 * @param ?WP_Post $order     The order post object.
		 */
		$value = apply_filters( 'tec_tickets_commerce_square_create_from_cart', $value, $items, $source_id, $order );

		if ( ! $value instanceof Value && is_numeric( $value ) ) {
			$value = Value::create( $value );
		}

		// Ensure we have a Value object returned from the filters.
		if ( ! $value instanceof Value ) {
			throw new RuntimeException( esc_html__( 'Value object not returned from filter', 'event-tickets' ) );
		}

		$payment = static::create( $source_id, $value, $order );

		if ( is_wp_error( $payment ) ) {
			return $payment;
		}

		return $payment['payment'];
	}

	/**
	 * Get a payment by ID.
	 *
	 * @since TBD
	 *
	 * @param string $payment_id The payment ID.
	 *
	 * @return ?array The payment data.
	 */
	public static function get( string $payment_id ): ?array {
		if ( ! $payment_id ) {
			return null;
		}

		$query_args = [];
		$body       = [];

		// Prepare the request arguments.
		$args = [
			'body' => $body,
		];

		return tribe( Requests::class )->get( "payments/{$payment_id}", $query_args, $args );
	}

	/**
	 * Update a payment.
	 *
	 * @since TBD
	 *
	 * @param string $payment_id The payment ID.
	 * @param array  $data       The payment data to update.
	 *
	 * @return ?array The updated payment data.
	 */
	public static function update( string $payment_id, array $data ): ?array {
		if ( ! $payment_id ) {
			return null;
		}

		$query_args = [];
		$body       = $data;

		// Prepare the request arguments.
		$args = [
			'body' => $body,
		];

		return tribe( Requests::class )->put( "payments/{$payment_id}", $query_args, $args );
	}

	/**
	 * Cancel a payment.
	 *
	 * @since TBD
	 *
	 * @param string $payment_id The payment ID.
	 *
	 * @return ?array The cancelled payment data.
	 */
	public static function cancel( string $payment_id ): ?array {
		if ( ! $payment_id ) {
			return null;
		}

		$query_args = [];
		$body       = [
			'idempotency_key' => uniqid( 'tec-square-cancel-', true ),
		];

		// Prepare the request arguments.
		$args = [
			'body' => $body,
		];

		return tribe( Requests::class )->post( "payments/{$payment_id}/cancel", $query_args, $args );
	}

	/**
	 * Format error message for display.
	 *
	 * @since TBD
	 *
	 * @param array $errors The errors array from Square API.
	 *
	 * @return string The formatted error message.
	 */
	public static function compile_errors( array $errors ): string {
		if ( empty( $errors ) ) {
			return '';
		}

		$error_message = '';

		if ( isset( $errors['errors'] ) && is_array( $errors['errors'] ) ) {
			foreach ( $errors['errors'] as $error ) {
				if ( ! empty( $error['detail'] ) ) {
					$error_message .= '<p>' . esc_html( $error['detail'] ) . '</p>';
				} elseif ( ! empty( $error['message'] ) ) {
					$error_message .= '<p>' . esc_html( $error['message'] ) . '</p>';
				}
			}
		} elseif ( is_string( $errors ) ) {
			$error_message = '<p>' . esc_html( $errors ) . '</p>';
		}

		return $error_message;
	}
}
