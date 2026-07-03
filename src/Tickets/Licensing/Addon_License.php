<?php
/**
 * Determines whether a companion add-on plugin is active and licensed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Licensing
 */

namespace TEC\Tickets\Licensing;

use TEC\Common\Libraries\Harbor;
use Throwable;
use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Class Addon_License
 *
 * @since TBD
 *
 * @package TEC\Tickets\Licensing
 */
class Addon_License {
	/**
	 * Whether an add-on plugin is active and licensed.
	 *
	 * Accepts either a Harbor unified license that covers the add-on's Harbor/Uplink product
	 * slug, or a legacy per-plugin license key that has not yet been migrated to Harbor.
	 *
	 * @since TBD
	 *
	 * @param string $main_class  Fully qualified name of a class that only exists when the add-on is active.
	 * @param string $harbor_slug The add-on's Harbor/Uplink product slug (e.g. `event-tickets-plus`).
	 *
	 * @return bool
	 */
	public function is_active( string $main_class, string $harbor_slug ): bool {
		if ( ! class_exists( $main_class ) ) {
			return false;
		}

		if ( tribe( Harbor::class )->is_product_licensed( $harbor_slug ) ) {
			return true;
		}

		return $this->has_valid_legacy_license( $harbor_slug );
	}

	/**
	 * Checks the Uplink resource registered by the add-on for a valid legacy license.
	 *
	 * @since TBD
	 *
	 * @param string $harbor_slug The add-on's Harbor/Uplink product slug.
	 *
	 * @return bool
	 */
	private function has_valid_legacy_license( string $harbor_slug ): bool {
		try {
			$resource = get_resource( $harbor_slug );
		} catch ( Throwable $e ) {
			return false;
		}

		if ( ! $resource ) {
			return false;
		}

		// The cached validity status can outlive the key it was validated for: clearing a
		// key does not revalidate or reset the status, so a stale "valid" flag can remain
		// even once the key itself is gone. Require a key to still be present.
		return '' !== $resource->get_license_key() && $resource->has_valid_license();
	}
}
