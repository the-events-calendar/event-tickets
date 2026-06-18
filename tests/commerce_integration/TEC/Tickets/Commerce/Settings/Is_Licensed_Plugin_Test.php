<?php

namespace TEC\Tickets\Commerce\Settings;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Gateways\Stripe\Application_Fee;
use TEC\Tickets\Commerce\Settings;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe\Tests\Traits\With_Uopz;

/**
 * Tests for Settings::is_licensed_plugin() caching behavior.
 *
 * @since 5.28.4.1
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
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE__Checker_Stub', 'validate_key', [ 'status' => 1 ] );

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
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE__Checker_Stub', 'validate_key', [ 'status' => 1 ] );

		set_transient( self::CACHE_KEY, wp_json_encode( false ), HOUR_IN_SECONDS );

		Settings::clear_licensed_plugin_cache();

		$this->assertTrue( Settings::is_licensed_plugin() );
	}

	/**
	 * Ensures the fee is not waived when Event Tickets Plus is not active.
	 *
	 * @test
	 */
	public function should_return_false_when_event_tickets_plus_is_not_active(): void {
		if ( did_action( 'tec_tickets_plus_fully_loaded' ) ) {
			$this->markTestSkipped( 'Event Tickets Plus is loaded in this environment.' );
		}

		$this->assertFalse( Settings::is_licensed_plugin() );
	}

	/**
	 * Ensures a stale cached valid license does not waive fees after invalidation.
	 *
	 * @test
	 */
	public function should_not_trust_stale_cached_valid_license(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', false );

		set_transient( self::CACHE_KEY, wp_json_encode( true ), HOUR_IN_SECONDS );

		$this->assertFalse( Settings::is_licensed_plugin() );
	}

	/**
	 * Ensures the Stripe application fee is applied without a valid license.
	 *
	 * @test
	 */
	public function should_apply_application_fee_when_license_is_invalid(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', false );

		set_transient( self::CACHE_KEY, wp_json_encode( true ), HOUR_IN_SECONDS );

		$value = new Value( 100.0 );
		$fee   = Application_Fee::calculate( $value );

		$this->assertFalse( Settings::is_licensed_plugin() );
		$this->assertGreaterThan( 0, $fee->get_integer(), 'Application fee should apply without a valid license.' );
	}

	/**
	 * Ensures checkout uses a cached invalid license result without revalidating.
	 *
	 * @test
	 */
	public function should_use_cached_invalid_result_during_checkout_without_revalidating(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', false );

		set_transient( self::CACHE_KEY, wp_json_encode( false ), HOUR_IN_SECONDS );

		$this->assertFalse( Settings::is_licensed_plugin() );
	}

	/**
	 * Ensures stale Uplink state does not waive fees when server validation fails.
	 *
	 * @test
	 */
	public function should_apply_fee_when_local_license_check_is_stale(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE', 'is_current_license_valid', true );
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE__Checker_Stub', 'validate_key', [ 'status' => 0 ] );

		$this->assertFalse( Settings::is_licensed_plugin() );

		$value = new Value( 100.0 );
		$fee   = Application_Fee::calculate( $value );

		$this->assertGreaterThan( 0, $fee->get_integer(), 'Application fee should apply when server validation fails.' );
	}

	/**
	 * Ensures fees apply when Event Tickets Plus is active without a license key.
	 *
	 * @test
	 */
	public function should_apply_fee_when_license_key_is_empty(): void {
		$this->register_pue_stub();
		$this->set_class_fn_return( 'Tribe__Tickets_Plus__PUE__Checker_Stub', 'get_key', '' );

		$this->assertFalse( Settings::is_licensed_plugin() );
	}

	/**
	 * Registers a minimal Event Tickets Plus PUE stub for tests.
	 *
	 * @return void
	 */
	private function register_pue_stub(): void {
		require_once dirname( __DIR__, 5 ) . '/_support/Stubs/Tribe__Tickets_Plus__PUE_Stub.php';

		tribe_singleton( \Tribe__Tickets_Plus__PUE::class, new \Tribe__Tickets_Plus__PUE() );

		if ( ! did_action( 'tec_tickets_plus_fully_loaded' ) ) {
			do_action( 'tec_tickets_plus_fully_loaded' );
		}
	}
}
