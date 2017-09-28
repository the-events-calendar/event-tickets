<?php

class Tribe__Tickets__Commerce__PayPal__Handler__Invalid implements Tribe__Tickets__Commerce__PayPal__Handler__Interface {

	/**
	 * Checks the request to see if payment data was communicated
	 *
	 * @since TBD
	 */
	public function check_response() {
		//noop
	}

	/**
	 * Validates a PayPal transaction ensuring that it is authentic
	 *
	 * @since TBD
	 *
	 * @param $transaction
	 *
	 * @return array|bool
	 */
	public function validate_transaction( $transaction = null ) {
		return false;
	}
}