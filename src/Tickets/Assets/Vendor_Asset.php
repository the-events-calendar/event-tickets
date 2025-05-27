<?php
/**
 * Vendor Asset class.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Assets;

use TEC\Common\Asset;

/**
 * Class Vendor_Asset.
 *
 * This class is used to represent an asset that is not managed by the plugin. As such,
 * it does not have a version or a minified version. It also does not do any local file
 * processing.
 *
 * @since 5.21.0
 *
 * @deprecated 5.23.0
 */
class Vendor_Asset extends Asset {
}
