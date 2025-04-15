<?php
// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase
namespace TEC\Tickets\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Contracts\Abstract_WhoDat;

/**
 * Class WhoDat. Handles connection to Stripe when the platform keys are needed.
 *
 * @since   5.3.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Stripe
 */
class WhoDat extends Abstract_WhoDat {

	/**
	 * The API Path.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected const API_ENDPOINT = 'stripe';

	/**
	 * Creates a new account link for the client and redirects the user to setup the account details.
	 *
	 * @since 5.3.0
	 * @deprecated TBD
	 *
	 * @return void
	 */
	public function connect_account(): void {
		_deprecated_function( __METHOD__, 'TBD' );
	}

	/**
	 * De-authorize the current seller account in Stripe oAuth.
	 *
	 * @since 5.3.0
	 * @deprecated TBD
	 *
	 * @return string
	 */
	public function disconnect_account() {
		_deprecated_function( __METHOD__, 'TBD' );
		return '';
	}

	/**
	 * Register a newly connected Stripe account to the website.
	 *
	 * @since 5.3.0
	 *
	 * @param array $account_data array of data returned from Stripe after a successful connection.
	 */
	public function onboard_account( $account_data ) {
		_deprecated_function( __METHOD__, 'TBD' );
		return [];
	}

	/**
	 * Requests WhoDat to refresh the oAuth tokens.
	 *
	 * @since 5.3.0
	 * @deprecated TBD
	 *
	 * @return string
	 */
	public function refresh_token() {
		_deprecated_function( __METHOD__, 'TBD' );
		return '';
	}
}
