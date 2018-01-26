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

	/**
	 * Returns the configuration status of the handler.
	 *
	 * @since TBD
	 *
	 * @param string $field Which configuration status field to return, either `slug` or `label`
	 * @param string  $slug Optionally return the specified field for the specified status.
	 *
	 * @return bool|string The current, or specified, configuration status slug or label
	 *                     or `false` if the specified field or slug was not found.
	 */
	public function get_config_status( $field = 'slug', $slug = null );

}