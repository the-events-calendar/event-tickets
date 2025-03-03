<?php
/**
 * Provides common methods to interact with the oAuth token.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */

namespace TEC\Tickets\Seating\Service;

use RuntimeException;
use TEC\Common\StellarWP\Uplink\Resources\Resource;
use function TEC\Common\StellarWP\Uplink\get_authorization_token;
use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Trait OAuth_Token.
 *
 * @since 5.16.0
 *
 * @package TEC\Controller\Service;
 */
trait OAuth_Token {
	/**
	 * Returns the OAuth token used to authenticate the site in the service.
	 *
	 * @since 5.16.0
	 *
	 * @return string|null The OAuth token, or `null` if there is no OAuth token.
	 */
	protected function get_oauth_token(): ?string {
		$memoize      = tribe_cache();
		$cache_key    = 'tec_seating_access_token';
		$access_token = $memoize[ $cache_key ];

		if ( ! ( $access_token && is_string( $access_token ) ) ) {
			try {
				$access_token = get_authorization_token( 'tec-seating' );
			} catch ( RuntimeException $e ) {
				$access_token = null;
			}
			$memoize[ $cache_key ] = $access_token;
		}

		return $access_token;
	}

	/**
	 * Updates the OAuth token used to authenticate the site in the service.
	 *
	 * @since 5.16.0
	 *
	 * @param string $token The OAuth token to set.
	 *
	 * @return void
	 */
	protected function set_oauth_token( string $token ): void {
		try {
			/** @var Resource|null $resource */
			$resource = get_resource( 'tec-seating' );
			$resource->store_token( $token );
		} catch ( RuntimeException $e ) {
			return;
		}
	}
}
