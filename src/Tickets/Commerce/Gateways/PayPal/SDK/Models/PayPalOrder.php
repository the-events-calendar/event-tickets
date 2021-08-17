<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models;

use InvalidArgumentException;
use stdClass;

/**
 * Class PayPalOrder
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class PayPalOrder {

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
	public $createTime;

	/**
	 * Order update time.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $updateTime;

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
	private $purchaseUnit;

	/**
	 * Payment details for order.
	 *
	 * @since 5.1.6
	 *
	 * @var PayPalPayment
	 */
	public $payment;

	/**
	 * Create PayPalOrder object from given array.
	 *
	 * @since 5.1.6
	 *
	 * @param $array
	 *
	 * @return PayPalOrder
	 */
	public static function fromArray( $array ) {
		/* @var PayPalOrder $order */
		$order = tribe( __CLASS__ );

		$order->validate( $array );

		// @todo Replace this with a new method somewhere else.
		$array = ArrayDataSet::camelCaseKeys( $array );

		foreach ( $array as $itemName => $value ) {
			if ( 'purchaseUnits' === $itemName ) {
				$value = current( $value );

				$order->purchaseUnit = $value;
				$order->payment      = PayPalPayment::fromArray( (array) current( $order->purchaseUnit->payments->captures ) );

				continue;
			}

			$order->{$itemName} = $value;
		}

		return $order;
	}

	/**
	 * Validate order given in array format.
	 *
	 * @since 5.1.6
	 *
	 * @param array $array
	 *
	 * @throws InvalidArgumentException
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
