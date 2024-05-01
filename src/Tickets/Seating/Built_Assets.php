<?php
/**
 * Provides methods to register built assets, from the `/build` directory of the plugin.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use Tribe__Assets as Assets;
use Tribe__Tickets__Main as Tickets;

/**
 * Trait Built_Assets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Seating;
 */
trait Built_Assets {
	/**
	 * Registers a built asset for the Seating feature.
	 *
	 * @since TBD
	 *
	 * @param string $handle The handle of the asset.
	 * @param string $path The file path from the `/build/seating` directory of the plugin.
	 * @param string[] $dependencies An array of dependencies.
	 * @param string|null $action The action to enqueue the asset on.
	 * @param array<string,array|callable> $localize A map of the data to localize.
	 */
	protected function register_built_asset(
		string $handle,
		string $path,
		array $dependencies = [],
		string $action = null,
		array $localize = []
	): void {
		$plugin    = Tickets::instance();
		$build_url = $plugin->plugin_url . 'build';

		Assets::register(
			Tickets::instance(),
			$handle,
			$build_url . '/seating/' . $path,
			$dependencies,
			$action,
			[ 'groups' => 'tec-tickets-seating', 'localize' => $localize ]
		);
	}
}