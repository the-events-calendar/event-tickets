<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Merchant Interface
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface Merchant_Interface {

	/**
	 * Gets the account key.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function get_account_key();

	/**
	 * Save merchant data.
	 *
	 * @since 5.3.0
	 *
	 * @return boolean
	 */
	public function save();

	/**
	 * Transforms the Merchant data into an array.
	 *
	 * @since 5.3.0
	 *
	 * @return array
	 */
	public function to_array();

	/**
	 * Creates this object from an array.
	 *
	 * @since 5.3.0
	 *
	 * @param array   $data
	 * @param boolean $needs_save
	 *
	 * @return boolean
	 */
	public function from_array( array $data, $needs_save = true );

}