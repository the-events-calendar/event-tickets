<?php
/**
 * Minimal PUE stub for license cache tests.
 *
 * @package TEC\Tickets\Tests\Support\Stubs
 */

if ( ! class_exists( 'Tribe__Tickets_Plus__PUE', false ) ) {
	/**
	 * Minimal PUE stub for license cache tests.
	 */
	class Tribe__Tickets_Plus__PUE {
		/**
		 * @param bool $revalidate Whether to revalidate the license.
		 *
		 * @return bool
		 */
		public function is_current_license_valid( $revalidate = false ) {
			return false;
		}
	}
}
