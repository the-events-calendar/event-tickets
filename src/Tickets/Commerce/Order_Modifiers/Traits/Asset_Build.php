<?php
/**
 * Asset build trait.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

use TEC\Common\StellarWP\Assets\Asset;
use Tribe__Tickets__Main as Tickets;

/**
 * Trait Asset_Build
 *
 * @since TBD
 */
trait Asset_Build {

	/**
	 * The asset group.
	 *
	 * @var string
	 */
	protected string $asset_group = 'tec-tickets-order-modifiers';

	/**
	 * The Tickets plugin instance.
	 *
	 * @var ?Tickets
	 */
	protected ?Tickets $plugin;

	/**
	 * Get the built asset URL for the Order Modifiers feature.
	 *
	 * @since TBD
	 *
	 * @param string $path The file path from the `/build/OrderModifiers` directory of the plugin.
	 */
	protected function get_built_asset_url( string $path ): string {
		$path = ltrim( trim( $path ), '/' );

		return "{$this->get_plugin_url()}build/OrderModifiers/{$path}";
	}

	/**
	 * Get the plugin URL.
	 *
	 * @since TBD
	 *
	 * @return string The plugin URL.
	 */
	protected function get_plugin_url(): string {
		if ( ! $this->plugin ) {
			$this->plugin = Tickets::instance();
		}

		return $this->plugin->plugin_url;
	}

	/**
	 * Add an asset to the list of registered assets.
	 *
	 * @since TBD
	 *
	 * @param string  $handle  The asset handle.
	 * @param string  $path    The asset path, relative to the build/OrderModifiers/ directory.
	 * @param ?string $version (Optional) The asset version. Defaults to the plugin version.
	 *
	 * @return Asset The asset instance.
	 */
	protected function add_asset( string $handle, string $path, ?string $version = null ): Asset {
		// Add the asset object.
		$asset = Asset::add(
			$handle,
			$this->get_built_asset_url( $path ),
			$version ?? Tickets::VERSION
		);

		// Add the asset to the order modifiers group.
		$asset->add_to_group( $this->asset_group );

		return $asset;
	}
}
