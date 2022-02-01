<?php

namespace TEC\Tickets\Commerce\Gateways\Contracts;

/**
 * Signup Interface.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\Contracts
 */
interface Signup_Interface {

	/**
	 * Gets the content for the template used for the sign-up link.
	 *
	 * @since TBD
	 *
	 * @return false|string
	 */
	public function get_link_html();

	/**
	 * Gets the template instance used to setup the rendering of the page.
	 *
	 * @since TBD moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @return \Tribe__Template
	 */
	public function get_template();

	/**
	 * Gets the saved hash for a given user, empty when non-existent.
	 *
	 * @since TBD moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @return array
	 */
	public function get_transient_data();

	/**
	 * Saves the URL in a transient for later use.
	 *
	 * @since TBD moved to Abstract_Signup
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
	 * @since TBD moved to Abstract_Signup
	 * @since 5.1.9
	 *
	 * @return bool
	 */
	public function delete_transient_data();
}