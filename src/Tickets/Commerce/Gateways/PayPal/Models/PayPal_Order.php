<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Models;

use InvalidArgumentException;
use stdClass;

/**
 * Class PayPalOrder
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class PayPal_Order {

	/**
	 * Order Id.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Order intent.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $intent;

	/**
	 * Order status.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Order creation time.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $create_time;

	/**
	 * Order update time.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $update_time;

	/**
	 * PayPal Order action links.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $links;

	/**
	 * Payer information.
	 *
	 * @since 5.1.6
	 *
	 * @var stdClass
	 */
	public $payer;

	/**
	 * Order purchase unit details.
	 *
	 * @since 5.1.6
	 *
	 * @var stdClass
	 */
	private $purchase_unit;

	/**
	 * Payment details for order.
	 *
	 * @since 5.1.6
	 *
	 * @var PayPal_Payment
	 */
	public $payment;

	/**
	 * Create PayPalOrder object from given array.
	 *
	 * @since 5.1.6
	 *
	 * @param $array
	 *
	 * @return PayPal_Order
	 */
	public static function from_array( $array ) {
		/* @var PayPal_Order $order */
		$order = tribe( __CLASS__ );

		$order->validate( $array );

		foreach ( $array as $item_name => $value ) {
			if ( 'purchase_units' === $item_name ) {
				$value = current( $value );

				$order->purchase_unit = $value;
				$order->payment       = PayPal_Payment::from_array( (array) current( $order->purchase_unit->payments->captures ) );

				continue;
			}

			$order->{$item_name} = $value;
		}

		return $order;
	}

	/**
	 * Validate order given in array format.
	 *
	 * @since 5.1.6
	 *
	 * @throws InvalidArgumentException
	 *
	 * @param array $array
	 *
	 */
	private function validate( $array ) {
		$required = [
			'id',
			'intent',
			'purchase_units',
			'create_time',
			'update_time',
			'links',
		];

		// PayPal does not send following parameter in Order (completed with advanced card fields payment method) details.
		if ( ! isset( $array['payment_source'] ) ) {
			$required = array_merge( $required, [ 'payer', 'status' ] );
		}

		// Remove empty values.
		$array = array_filter( $array );

		if ( array_diff( $required, array_keys( $array ) ) ) {
			throw new InvalidArgumentException(
				sprintf(
					esc_html__( 'To create a PayPalOrder object, please provide valid %1$s', 'event-tickets' ),
					implode( ', ', $required )
				)
			);
		}
	}
}
