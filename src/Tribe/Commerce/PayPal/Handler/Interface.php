<?php
interface Tribe__Tickets__Commerce__PayPal__Handler__Interface {

	/**
	 * Checks the request to see if payment data was communicated
	 *
	 * @since TBD
	 */
	public function check_response();

	/**
	 * Validates a PayPal transaction ensuring that it is authentic
	 *
	 * @since TBD
	 *
	 * @param string $transaction
	 *
	 * @return array|bool
	 */
	public function validate_transaction( $transaction = null );
}