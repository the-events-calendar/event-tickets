<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models;

use InvalidArgumentException;
use stdClass;

/**
 * Class PayPalOrder
 *
 * @since TBD
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class PayPalOrder {

	/**
	 * Order Id.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Order intent.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $intent;

	/**
	 * Order status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Order creation time.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $createTime;

	/**
	 * Order update time.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $updateTime;

	/**
	 * PayPal Order action links.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $links;

	/**
	 * Payer information.
	 *
	 * @since TBD
	 *
	 * @var stdClass
	 */
	public $payer;

	/**
	 * Order purchase unit details.
	 *
	 * @since TBD
	 *
	 * @var stdClass
	 */
	private $purchaseUnit;

	/**
	 * Payment details for order.
	 *
	 * @since TBD
	 *
	 * @var PayPalPayment
	 */
	public $payment;

	/**
	 * Create PayPalOrder object from given array.
	 *
	 * @since TBD
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
	 * @since TBD
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
