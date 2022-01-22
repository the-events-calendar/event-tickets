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

	public function get_account_key();

	public function save();

	public function to_array();

	public function from_array( array $data, $needs_save = true );

}