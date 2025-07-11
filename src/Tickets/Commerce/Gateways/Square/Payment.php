<?php
/**
 * Square Payment Processing Class
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use RuntimeException;
use TEC\Tickets\Commerce\Utils\Value;
use WP_Post;

/**
 * Square payment processing class.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Payment {
	/**
	 * The key used to identify the Square refund ID.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const KEY_ORDER_REFUND_ID = '_tec_tc_order_gateway:square_refund_id';

	/**
	 * The key used to identify the time of the Square refund ID.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const KEY_ORDER_REFUND_ID_TIME = '_tec_tc_order_gateway:square_refund_id_time';

	/**
	 * The key used to identify the Square payment ID.
	 *
	 * Need to be passed by a sprintf, with mode being the first variable.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const KEY_ORDER_PAYMENT_ID = '_tec_tc_order_gateway:square_payment_id';

	/**
	 * The key used to identify the time of the Square payment ID
	 *
	 * Need to be passed by a sprintf, with mode being the first variable and the payment ID being the second variable.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public const KEY_ORDER_PAYMENT_ID_TIME = '_tec_tc_order_gateway:square_payment_id_time:%s';

	/**
	 * The key used to identify Square payments created in Tickets Commerce.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $tc_metadata_identifier = 'tec_tc_square_payment';

	/**
	 * Create a payment from the provided Value object.
	 *
	 * @since 5.24.0
	 *
	 * @param string  $source_id       The source ID.
	 * @param Value   $value           The value object to create a payment for.
	 * @param WP_Post $order           The order post object.
	 * @param string  $square_order_id The Square order ID.
	 *
	 * @return ?array| The payment data.
	 */
	public static function create( string $source_id, Value $value, WP_Post $order, string $square_order_id = '' ): ?array {
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
			'order_id'        => $square_order_id,
			'reference_id'    => (string) $order->ID,
			'metadata'        => [
				static::$tc_metadata_identifier => true,
			],
		];

		/**
		 * Filters the payment body.
		 *
		 * @since 5.24.0
		 *
		 * @param array   $body The payment body.
		 * @param Value   $value The value object.
		 * @param WP_Post $order The order post object.
		 * @param string  $source_id The source ID.
		 */
		$body = apply_filters( 'tec_tickets_commerce_square_payment_body', $body, $value, $order, $source_id );

		$fee = Application_Fee::calculate( $value );

		if ( $fee->get_integer() > 0 ) {
			$body['app_fee_money'] = [
				'amount'   => (int) $fee->get_integer(),
				'currency' => $value->get_currency_code(),
			];
		}

		$args = [
			'body'    => $body,
			'headers' => [
				'Content-Type' => 'application/json',
			],
		];

		return Requests::post( 'payments', $query_args, $args );
	}

	/**
	 * Creates a payment from order.
	 *
	 * @since 5.24.0
	 *
	 * @param string  $source_id       The source ID.
	 * @param WP_Post $order           The order post object.
	 * @param string  $square_order_id The Square order ID.
	 *
	 * @return array The payment data.
	 *
	 * @throws RuntimeException If the value object is not returned from the filter.
	 */
	public static function create_from_order( string $source_id, WP_Post $order, string $square_order_id = '' ): array {
		$value = Value::create( $order->total );

		/**
		 * Filters the value and items before creating a Square payment.
		 *
		 * @since 5.24.0
		 *
		 * @param Value   $value     The total value of the cart.
		 * @param WP_Post $order     The order post object.
		 * @param string  $source_id The source ID.
		 */
		$value = apply_filters( 'tec_tickets_commerce_square_create_from_order', $value, $order, $source_id );

		if ( ! $value instanceof Value && is_numeric( $value ) ) {
			$value = Value::create( $value );
		}

		// Ensure we have a Value object returned from the filters.
		if ( ! $value instanceof Value ) {
			throw new RuntimeException( esc_html__( 'Value object not returned from filter', 'event-tickets' ) );
		}

		$payment = static::create( $source_id, $value, $order, $square_order_id );

		do_action( 'tribe_log', 'debug', 'Square Payment', [ $payment, $source_id, $value, $order, $square_order_id ] );

		return $payment['payment'] ?? [];
	}

	/**
	 * Get a payment by ID.
	 *
	 * @since 5.24.0
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

		return Requests::get_with_cache( "payments/{$payment_id}", $query_args, $args );
	}

	/**
	 * Update a payment.
	 *
	 * @since 5.24.0
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

		return Requests::put( "payments/{$payment_id}", $query_args, $args );
	}

	/**
	 * Cancel a payment.
	 *
	 * @since 5.24.0
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

		return Requests::post( "payments/{$payment_id}/cancel", $query_args, $args );
	}

	/**
	 * Format error message for display.
	 *
	 * @since 5.24.0
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
