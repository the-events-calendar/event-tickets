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
		set_transient( self::CACHE_KEY, wp_json_encode( true ), HOUR_IN_SECONDS );
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
	public function should_clear_cache_on_settings_save(): void {
		do_action( 'tribe_settings_save' );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_when_license_option_updates(): void {
		do_action( 'update_option_pue_install_key_event_tickets_plus' );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_when_event_tickets_plus_uplink_connects(): void {
		$plugin = new class() {
			public function get_slug() {
				return 'event-tickets-plus';
			}
		};

		$this->hooks->maybe_clear_licensed_plugin_cache_on_uplink_change( $plugin );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_when_event_tickets_plus_uplink_disconnects_with_slug(): void {
		$this->hooks->maybe_clear_licensed_plugin_cache_on_uplink_change( 'event-tickets-plus' );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_not_clear_cache_for_other_uplink_plugins(): void {
		$this->hooks->maybe_clear_licensed_plugin_cache_on_uplink_change( 'the-events-calendar' );

		$this->assertNotFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_clear_cache_when_event_tickets_plus_is_deactivated(): void {
		$this->hooks->maybe_clear_licensed_plugin_cache_on_plugin_change( 'event-tickets-plus/event-tickets-plus.php', false );

		$this->assertFalse( get_transient( self::CACHE_KEY ) );
	}

	/**
	 * @test
	 */
	public function should_not_clear_cache_for_other_plugins(): void {
		$this->hooks->maybe_clear_licensed_plugin_cache_on_plugin_change( 'event-tickets/event-tickets.php', false );

		$this->assertNotFalse( get_transient( self::CACHE_KEY ) );
	}
}
