<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SDK\Models;

use InvalidArgumentException;

/**
 * Class PayPalPayment
 *
 * @since 5.1.6
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 *
 */
class PayPal_Payment {

	/**
	 * Payment Id.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Payment Amount.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $amount;

	/**
	 * Payment status.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Payment creation time.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $create_time;

	/**
	 * Payment update time.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $update_time;

	/**
	 * PayPal Payment action links.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	public $links;

	/**
	 *
	 */
	/**
	 * Create PayPalPayment object from given array.
	 *
	 * @since 5.1.6
	 *
	 * @param $array
	 *
	 * @return PayPal_Payment
	 */
	public static function from_array( $array ) {
		/* @var PayPal_Payment $payment */
		$payment = tribe( __CLASS__ );

		$payment->validate( $array );

		// @todo Replace this with a new method somewhere else.
		$array = ArrayDataSet::camelCaseKeys( $array );

		foreach ( $array as $item_name => $value ) {
			$payment->{$item_name} = $value;
		}

		return $payment;
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
		$required = [ 'id', 'amount', 'status', 'create_time', 'update_time', 'links' ];
		$array    = array_filter( $array ); // Remove empty values.

		if ( array_diff( $required, array_keys( $array ) ) ) {
			throw new InvalidArgumentException( __( 'To create a PayPalPayment object, please provide valid id, amount, status, create_time, update_time and links', 'event-tickets' ) );
		}
	}
}
