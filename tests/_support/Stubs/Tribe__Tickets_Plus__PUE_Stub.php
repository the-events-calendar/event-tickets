<?php
/**
 * Minimal PUE stub for license cache tests.
 *
 * @package TEC\Tickets\Tests\Support\Stubs
 */

if ( ! class_exists( 'Tribe__Tickets_Plus__PUE__Checker_Stub', false ) ) {
	/**
	 * Minimal PUE Checker stub for license cache tests.
	 */
	class Tribe__Tickets_Plus__PUE__Checker_Stub {
		/**
		 * @return string
		 */
		public function get_key() {
			return 'test-license-key';
		}

		/**
		 * @param string $key The license key.
		 *
		 * @return array<string, mixed>
		 */
		public function validate_key( $key ) {
			unset( $key );

			return [ 'status' => 0 ];
		}
	}
}

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
			unset( $revalidate );

			return false;
		}

		/**
		 * @return Tribe__Tickets_Plus__PUE__Checker_Stub
		 */
		public function get_pue() {
			return new Tribe__Tickets_Plus__PUE__Checker_Stub();
		}
	}
}
