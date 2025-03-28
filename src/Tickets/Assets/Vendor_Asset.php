<?php
/**
 * Vendor Asset class.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Assets;

use TEC\Common\StellarWP\Assets\Asset;

/**
 * Class Vendor_Asset.
 *
 * This class is used to represent an asset that is not managed by the plugin. As such,
 * it does not have a version or a minified version. It also does not do any local file
 * processing.
 *
 * @since 5.21.0
 */
class Vendor_Asset extends Asset {

	/**
	 * Whether or not to attempt to load an .asset.php file.
	 *
	 * @since 5.21.0
	 *
	 * @var bool
	 */
	protected bool $use_asset_file = false;

	/**
	 * Constructor.
	 *
	 * @since 5.21.0
	 *
	 * @param string $slug The asset slug.
	 * @param string $url  The asset URL.
	 * @param string $type The asset type.
	 */
	public function __construct( string $slug, string $url, string $type = 'js' ) {
		$this->slug      = sanitize_key( $slug );
		$this->type      = strtolower( $type );
		$this->url       = $url;
		$this->is_vendor = true;
	}

	/**
	 * Get the asset url.
	 *
	 * Note: This does NOT make use of the `$use_min_if_available` parameter.
	 *
	 * @since 5.21.0
	 *
	 * @param bool $use_min_if_available [UNUSED] Whether to use the minified version of the asset if available.
	 * @return string
	 */
	public function get_url( bool $use_min_if_available = true ): string {
		return $this->url;
	}

	/**
	 * Get the asset version.
	 *
	 * This always returns an empty string.
	 *
	 * @since 5.21.0
	 *
	 * @return string
	 */
	public function get_version(): string {
		return '';
	}
}
