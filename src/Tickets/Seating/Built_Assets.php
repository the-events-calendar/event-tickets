<?php
/**
 * Provides methods to register built assets for the Seating feature.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use Tribe__Tickets__Main as Tickets;

/**
 * Trait Built_Assets.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Seating;
 */
trait Built_Assets {
	/**
	 * Returns the built asset URL for the Seating feature.
	 *
	 * @since 5.16.0
	 *
	 * @param string $path The file path from the `/build/seating` directory of the plugin.
	 */
	protected function built_asset_url( string $path ): string {
		$plugin = Tickets::instance();

		return $plugin->plugin_url . 'build/Seating/' . ltrim( $path, '/' );
	}
}
