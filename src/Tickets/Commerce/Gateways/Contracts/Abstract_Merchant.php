<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use Tribe\Tickets\Admin\Settings as Tickets_Settings;
use TEC\Tickets\Commerce\Payments_Tab;
use TEC\Tickets\Commerce\Traits\Has_Mode;

/**
 * Abstract Merchant Contract.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
abstract class Abstract_Merchant implements Merchant_Interface {
	use Has_Mode;

	/**
	 * Stores the nonce action for disconnecting this merchant, needs to be overwritten by the child class.
	 *
	 * @since 5.11.0.5
	 *
	 * @var string
	 */
	protected string $disconnect_action;

	/**
	 * Handle initial setup for the object singleton.
	 *
	 * @since 5.1.9
	 */
	public function init() {
		$this->from_array( $this->get_details_data(), false );
	}

	/**
	 * Make Merchant object from array.
	 *
	 * @since 5.1.9
	 *
	 * @param array   $data       Which values need to .
	 * @param boolean $needs_save Determines if the proprieties saved need to save to the DB.
	 *
	 * @return boolean
	 */
	public function from_array( array $data, $needs_save = true ) {
		if ( ! $this->validate( $data ) ) {
			return false;
		}

		$this->setup_properties( $data, $needs_save );

		return true;
	}

	/**
	 * Gets the value stored for the Client ID.
	 *
	 * @since 5.3.0 moved to Abstract_Merchant
	 * @since 5.1.9
	 *
	 * @return string
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Save merchant details.
	 *
	 * @since 5.3.0 moved to Abstract_Merchant
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function save() {
		if ( false === $this->needs_save() ) {
			return false;
		}

		$saved = update_option( $this->get_account_key(), $this->to_array() );

		// If we were able to save, we reset the needs save.
		if ( $saved ) {
			$this->needs_save = false;
		}

		return $saved;
	}

	/**
	 * Returns the URL to disconnect the merchant.
	 *
	 * @since 5.11.0.5
	 *
	 * @return string
	 */
	public function get_disconnect_url(): string {
		$current_section = Payments_Tab::$key_current_section_get_var;

		return (string) tribe( Tickets_Settings::class )->get_url(
			[
				'tab'            => Payments_Tab::$slug,
				$current_section => Gateway::get_key(),
				'tc-action'      => $this->get_disconnect_action(),
				'tc-nonce'       => wp_create_nonce( $this->get_disconnect_action() ),
			]
		);
	}

	/**
	 * Returns the action to use for disconnecting the merchant.
	 *
	 * @since 5.11.0.5
	 *
	 * @return string
	 */
	public function get_disconnect_action(): string {
		return $this->disconnect_action;
	}
}