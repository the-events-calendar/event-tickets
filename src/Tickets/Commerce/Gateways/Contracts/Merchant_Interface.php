<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Merchant Interface
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface Merchant_Interface {

	/**
	 * Gets the account key.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_account_key();

	/**
	 * Save merchant data.
	 *
	 * @since TBD
	 *
	 * @return boolean
	 */
	public function save();

	/**
	 * Transforms the Merchant data into an array.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public function to_array();

	/**
	 * Creates this object from an array.
	 *
	 * @since TBD
	 *
	 * @param array   $data
	 * @param boolean $needs_save
	 *
	 * @return boolean
	 */
	public function from_array( array $data, $needs_save = true );

}