<?php

namespace TEC\Tickets\Commerce;

/**
 * Notice Handler for managing Admin view notices.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce
 */
class Notice_Handler {

	/**
	 * Transient cache key from `\Tribe_Admin_Notices->get_transients()` method.
	 *
	 * @var string
	 */
	private static $transient_cache_key = 'transient_admin_notices';

	/**
	 * Add an admin notice that should only show once.
	 *
	 * @since TBD
	 *
	 * @param string $slug    Slug to store the notice.
	 * @param string $message Content to display as notice.
	 * @param string $type    Type of notice; Supported types: success | error | info | warning.
	 */
	public function admin_notice( $slug, $message, $type = 'error' ) {

		$expires_in_seconds = 10;

		$args = [
			'expire' => true,
			'wrap'   => 'h4',
			'type'   => $type,
		];

		tribe_transient_notice( $slug, $message, $args, $expires_in_seconds );

		// Clear the existing notices cache.
		$cache = tribe( 'cache' );
		unset( $cache[ self::$transient_cache_key ] );
	}
}