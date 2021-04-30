<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\Models;

use InvalidArgumentException;

/**
 * Class PayPalPayment
 *
 * @since TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce
 *
 */
class PayPalPayment {

	/**
	 * Payment Id.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $id;

	/**
	 * Payment Amount.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $amount;

	/**
	 * Payment status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $status;

	/**
	 * Payment creation time.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $createTime;

	/**
	 * Payment update time.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $updateTime;

	/**
	 * PayPal Payment action links.
	 *
	 * @since TBD
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
	 * @since TBD
	 *
	 * @param $array
	 *
	 * @return PayPalPayment
	 */
	public static function fromArray( $array ) {
		/* @var PayPalPayment $payment */
		$payment = tribe( __CLASS__ );

		$payment->validate( $array );

		// @todo Replace this with a new method somewhere else.
		$array = ArrayDataSet::camelCaseKeys( $array );

		foreach ( $array as $itemName => $value ) {
			$payment->{$itemName} = $value;
		}

		return $payment;
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
		$required = [ 'id', 'amount', 'status', 'create_time', 'update_time', 'links' ];
		$array    = array_filter( $array ); // Remove empty values.

		if ( array_diff( $required, array_keys( $array ) ) ) {
			throw new InvalidArgumentException( __( 'To create a PayPalPayment object, please provide valid id, amount, status, create_time, update_time and links', 'event-tickets' ) );
		}
	}
}
