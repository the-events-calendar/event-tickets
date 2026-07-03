<?php
/**
 * Tests for the Addon_License_Validator service.
 *
 * @since TBD
 */

namespace TEC\Tickets\Licensing;

use Codeception\TestCase\WPTestCase;
use TEC\Common\Libraries\Harbor;
use TEC\Common\StellarWP\Uplink\Register;
use TEC\Common\StellarWP\Uplink\Resources\Resource;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Main;

use function TEC\Common\StellarWP\Uplink\get_resource;

/**
 * Class Addon_License_Validator_Test
 *
 * @since TBD
 */
class Addon_License_Validator_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * The Addon_License_Validator instance under test.
	 *
	 * @var Addon_License_Validator
	 */
	private $addon_license;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->addon_license = new Addon_License_Validator();
	}

	/**
	 * Registers a fake Uplink resource for a slug, optionally seeding a license key and/or a
	 * cached license status option.
	 *
	 * @param string      $slug   The Harbor/Uplink product slug to register.
	 * @param string|null $key    License key to store, or null to leave the key empty.
	 * @param string|null $status Cached license status to store ('valid'|'invalid'), or null to leave unset.
	 *
	 * @return Resource
	 */
	private function register_legacy_resource( string $slug, ?string $key = null, ?string $status = null ): Resource {
		$resource = Register::plugin( $slug, 'Test Addon', '1.0.0', __DIR__, tribe( Tribe__Main::class ) );

		if ( null !== $key ) {
			$resource->set_license_key( $key, 'any' );
		}

		if ( null !== $status ) {
			update_option( $resource->get_license_object()->get_key_status_option_name(), $status );
		}

		return $resource;
	}

	public function test_it_returns_false_when_main_class_does_not_exist(): void {
		$this->assertFalse(
			$this->addon_license->is_active( 'Addon_License_Validator_Test_Nonexistent_Class', 'irrelevant-slug' )
		);
	}

	public function test_it_returns_true_when_harbor_reports_product_licensed(): void {
		$this->set_class_fn_return( Harbor::class, 'is_product_licensed', true );

		// No legacy resource is registered for this slug: the Harbor check alone must be enough.
		$this->assertTrue(
			$this->addon_license->is_active( self::class, 'addon-license-test-harbor-licensed' )
		);
	}

	public function test_it_returns_false_when_harbor_does_not_license_it_and_no_legacy_resource_exists(): void {
		$this->set_class_fn_return( Harbor::class, 'is_product_licensed', false );

		$this->assertFalse(
			$this->addon_license->is_active( self::class, 'addon-license-test-never-registered' )
		);
	}

	public function test_it_returns_true_when_legacy_resource_has_a_valid_license(): void {
		$this->set_class_fn_return( Harbor::class, 'is_product_licensed', false );

		$slug = 'addon-license-test-legacy-valid';
		$this->register_legacy_resource( $slug, 'a-real-license-key', 'valid' );

		$this->assertTrue( $this->addon_license->is_active( self::class, $slug ) );
	}

	public function test_it_returns_false_when_legacy_status_is_invalid(): void {
		$this->set_class_fn_return( Harbor::class, 'is_product_licensed', false );

		$slug = 'addon-license-test-legacy-invalid';
		$this->register_legacy_resource( $slug, 'a-real-license-key', 'invalid' );

		$this->assertFalse( $this->addon_license->is_active( self::class, $slug ) );
	}

	/**
	 * Regression test: the cached license status can outlive the key it was validated for,
	 * since clearing a key to empty never triggers revalidation. A stale "valid" status must
	 * not be trusted once the key itself is gone.
	 */
	public function test_it_returns_false_when_status_is_valid_but_key_is_empty(): void {
		$this->set_class_fn_return( Harbor::class, 'is_product_licensed', false );

		$slug = 'addon-license-test-stale-valid-status';
		// Deliberately do not seed a key: only the (stale) cached status is present.
		$this->register_legacy_resource( $slug, null, 'valid' );

		$this->assertFalse( $this->addon_license->is_active( self::class, $slug ) );
	}
}
