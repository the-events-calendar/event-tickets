<?php

namespace TEC\Tickets\Emails\JSON_LD;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Emails\Email\Completed_Order;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email_Abstract;

/**
 * Class Handler
 *
 * @since TBD
 *
 * @package TEC\Tickets\Emails\JSON_LD
 */
class Handler {

	/**
	 * Get the JSON LD data for the email.
	 *
	 * @param Email_Abstract $email The email object.
	 *
	 * @since TBD
	 *
	 * @return array The JSON LD data.
	 */
	public static function get_data( Email_Abstract $email ) : array {

		$data = [];

		if (
			$email::$slug === Completed_Order::$slug
			|| $email::$slug === Purchase_Receipt::$slug ) {
			$order = $email->get( 'order' );

			if ( empty( $order ) ) {
				return [];
			}

			return ( new Order_Schema( $order ) )->get_data();
		}

		return $data;
	}
}