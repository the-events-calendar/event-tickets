<?php
/**
 * Provides common methods to interact with the oAuth token.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

/**
 * Trait oAuth_Token.
 *
 * @since TBD
 *
 * @package TEC\Controller\Service;
 */
trait oAuth_Token {
	/**
	 * Returns the OAuth token used to authenticate the site in the service.
	 *
	 * @since TBD
	 *
	 * @return string|null The OAuth token, or `null` if there is no OAuth token.
	 */
	private function get_oauth_token(): ?string {
		return tribe_get_option( self::get_oauth_token_option_name(), null );
	}

	/**
	 * Returns the option name used to store the Service oAuth access token.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public static function get_oauth_token_option_name(): string {
		return 'events_assigned_seating_access_token';
	}
}