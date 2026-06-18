<?php

namespace TEC\Tickets\Commerce\Settings;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Settings;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Tests for Settings::is_licensed_plugin() caching behavior.
 *
 * @since TBD
 */
class Is_Licensed_Plugin_Test extends WPTestCase {

	use With_Uopz;

	/**
	 * Cache key used by Settings::is_licensed_plugin().
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'TEC\Tickets\Commerce\Settings::is_licensed_plugin';

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();

		Settings::clear_licensed_plugin_cache();
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown(): void {
		Settings::clear_licensed_plugin_cache();

		parent::tearDown();
	}

	/**
	 * Ensures revalidation bypasses a stale cached invalid license result.
	 *
	 * @test
	 */
	public function should_bypass_stale_cache_when_revalidating(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', true );

		set_transient( self::CACHE_KEY, wp_json_encode( false ), HOUR_IN_SECONDS );

		$this->assertTrue( Settings::is_licensed_plugin( true ) );
	}

	/**
	 * Ensures cached invalid results are returned when not revalidating.
	 *
	 * @test
	 */
	public function should_use_cached_value_when_not_revalidating(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', false );

		set_transient( self::CACHE_KEY, wp_json_encode( false ), HOUR_IN_SECONDS );

		$this->assertFalse( Settings::is_licensed_plugin() );
	}

	/**
	 * Ensures cache invalidation allows fresh license checks after activation.
	 *
	 * @test
	 */
	public function should_recheck_license_after_cache_is_cleared(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', true );

		set_transient( self::CACHE_KEY, wp_json_encode( false ), HOUR_IN_SECONDS );

		Settings::clear_licensed_plugin_cache();

		$this->assertTrue( Settings::is_licensed_plugin() );
	}

	/**
	 * Registers a minimal Event Tickets Plus PUE stub for tests.
	 *
	 * @return void
	 */
	private function register_pue_stub(): void {
		require_once dirname( __DIR__, 5 ) . '/_support/Stubs/Tribe__Tickets_Plus__PUE_Stub.php';

		tribe_singleton( \Tribe__Tickets_Plus__PUE::class, new \Tribe__Tickets_Plus__PUE() );
	}
}
