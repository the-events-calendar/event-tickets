<?php
/**
 * Asset build trait.
 *
 * @since 5.18.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Traits;

use TEC\Common\Asset;
use Tribe__Tickets__Main as Tickets;

/**
 * Trait Asset_Build
 *
 * @since 5.18.0
 */
trait Asset_Build {

	/**
	 * The asset group.
	 *
	 * @var string
	 */
	protected static $asset_group = 'tec-tickets-order-modifiers';

	/**
	 * Add an asset to the list of registered assets.
	 *
	 * @since 5.18.0
	 *
	 * @param string  $handle  The asset handle.
	 * @param string  $path    The asset path, relative to the build/OrderModifiers/ directory.
	 * @param ?string $version (Optional) The asset version. Defaults to the plugin version.
	 *
	 * @return Asset The asset instance.
	 */
	protected function add_asset( string $handle, string $path, ?string $version = null ): Asset {
		return Asset::add(
			$handle,
			$path,
			$version ?? Tickets::VERSION
		)->add_to_group( static::$asset_group )
		->add_to_group_path( 'et-order-modifiers' );
	}
}
