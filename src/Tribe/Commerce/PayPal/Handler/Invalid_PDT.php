<?php

/**
 * Class Tribe__Tickets__Commerce__PayPal__Handler__Invalid_PDT
 *
 * @since TBD
 */
class Tribe__Tickets__Commerce__PayPal__Handler__Invalid_PDT implements Tribe__Tickets__Commerce__PayPal__Handler__Interface {

	/**
	 * @var string
	 */
	protected $transaction;

	public function __construct( $transaction ) {
		$this->transaction = $transaction;
	}

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
		// no-op

		return false;
	}

	/**
	 * Saves the invalid transaction data to the database.
	 *
	 * @since TBD
	 */
	public function save_transaction() {
		// save the transaction for future reconciliation
		$transaction_object = new Tribe__Tickets__Commerce__PayPal__Transaction( $this->transaction );
		$transaction_object->set_status( Tribe__Tickets__Commerce__PayPal__Transaction::$unregistered_status );
		$transaction_object->set_data( 'handler', 'PDT' );
		$transaction_object->save();
	}
}