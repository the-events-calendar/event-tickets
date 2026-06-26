<?php

namespace TEC\Tickets\Commerce\Settings;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Hooks;
use TEC\Tickets\Commerce\Settings;

/**
 * Tests license cache invalidation hooks.
 *
 * @since TBD
 */
class Licensed_Plugin_Cache_Hooks_Test extends WPTestCase {

	/**
	 * Cache key used by Settings::is_licensed_plugin().
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'TEC\Tickets\Commerce\Settings::is_licensed_plugin';

	/**
	 * @var Hooks
	 */
	private $hooks;

	/**
	 * {@inheritdoc}
	 */
	public function setUp(): void {
		parent::setUp();

		$this->hooks = tribe( Hooks::class );
		$this->seed_license_cache();
	}

	/**
	 * {@inheritdoc}
	 */
	public function tearDown(): void {
		Settings::clear_licensed_plugin_cache();

		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function should_clear_cache_on_tribe_settings_save_hook(): void {
		do_action( 'tribe_settings_save' );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_on_update_option_pue_install_key_event_tickets_plus_hook(): void {
		do_action( 'update_option_pue_install_key_event_tickets_plus' );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_on_stellarwp_uplink_tec_connected_hook(): void {
		$plugin = new class() {
			public function get_slug() {
				return 'event-tickets-plus';
			}
		};

		do_action( 'stellarwp/uplink/tec/connected', $plugin );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_on_stellarwp_uplink_tec_disconnected_hook(): void {
		do_action( 'stellarwp/uplink/tec/disconnected', 'event-tickets-plus' );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_on_activated_plugin_hook(): void {
		do_action( 'activated_plugin', 'event-tickets-plus/event-tickets-plus.php', false );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_on_deactivated_plugin_hook(): void {
		do_action( 'deactivated_plugin', 'event-tickets-plus/event-tickets-plus.php', false );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_not_clear_cache_on_uplink_connected_for_other_plugins(): void {
		do_action( 'stellarwp/uplink/tec/connected', 'the-events-calendar' );

		$this->assertNotFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_not_clear_cache_on_uplink_disconnected_for_other_plugins(): void {
		do_action( 'stellarwp/uplink/tec/disconnected', 'the-events-calendar' );

		$this->assertNotFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_not_clear_cache_on_activated_plugin_for_other_plugins(): void {
		do_action( 'activated_plugin', 'event-tickets/event-tickets.php', false );

		$this->assertNotFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_not_clear_cache_on_deactivated_plugin_for_other_plugins(): void {
		do_action( 'deactivated_plugin', 'event-tickets/event-tickets.php', false );

		$this->assertNotFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * Seeds a cached license value for hook tests.
	 *
	 * @return void
	 */
	private function seed_license_cache(): void {
		set_transient( self::CACHE_KEY, wp_json_encode( true ), HOUR_IN_SECONDS );
	}
}
