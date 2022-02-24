<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Signup Interface.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface Signup_Interface {

	/**
	 * Gets the content for the template used for the sign-up link.
	 *
	 * @since 5.3.0
	 *
	 * @return false|string
	 */
	public function get_link_html();

	/**
	 * Gets the template instance used to setup the rendering of the page.
	 *
	 * @since 5.3.0 moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @return \Tribe__Template
	 */
	public function get_template();

	/**
	 * Gets the saved hash for a given user, empty when non-existent.
	 *
	 * @since 5.3.0 moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function get_transient_data();

	/**
	 * Saves the URL in a transient for later use.
	 *
	 * @since 5.3.0 moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @param string $value URL for signup.
	 *
	 * @return bool
	 */
	public function update_transient_data( $value );

	/**
	 * Delete url transient from the DB.
	 *
	 * @since 5.3.0 moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function delete_transient_data();
}