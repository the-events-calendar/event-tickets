<?php

namespace Tribe\Tickets;

use TEC\Tickets\Admin\Notice_New_Views_Upgrade;
use Tribe__Tickets__Main;
use Tribe__Tickets__Updater;

/**
 * Class NewViewsIsEnabledTest
 *
 * @package Tribe\Tickets
 *
 * @see     \tribe_tickets_new_views_is_enabled()
 * @see     \tribe_tickets_rsvp_new_views_is_enabled()
 * @see     \Tribe__Tickets__Updater::migrate_force_new_views()
 * @see     \Tribe__Tickets__Updater::force_new_views()
 */
class NewViewsIsEnabledTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * The options this test writes, and what they held before it did.
	 *
	 * WPTestCase rolls the database back, but Tribe keeps its own copy of the options array on the
	 * container, and that survives the rollback into every later test in the process.
	 *
	 * @var array
	 */
	protected array $original_options = [];

	/**
	 * @before
	 */
	public function remember_options(): void {
		foreach ( [ 'previous_event_tickets_versions', 'tickets_use_new_views', 'tickets_rsvp_use_new_views', 'event-tickets-schema-version', Notice_New_Views_Upgrade::OPTION_FORCED_ON ] as $option ) {
			$this->original_options[ $option ] = tribe_get_option( $option );
		}
	}

	/**
	 * @after
	 */
	public function restore_options(): void {
		foreach ( $this->original_options as $option => $value ) {
			tribe_update_option( $option, $value );
		}
	}

	/**
	 * @test
	 */
	public function it_should_enable_the_tickets_views_whatever_the_option_says() {
		$this->given_an_old_install_with_the_views_turned_off();

		$this->assertTrue( tribe_tickets_new_views_is_enabled() );
	}

	/**
	 * @test
	 */
	public function it_should_enable_the_rsvp_views_whatever_the_option_says() {
		$this->given_an_old_install_with_the_views_turned_off();

		$this->assertTrue( tribe_tickets_rsvp_new_views_is_enabled() );
	}

	/**
	 * Themes and plugins hold the only remaining way to turn the views off, and it stays.
	 *
	 * @test
	 */
	public function it_should_still_honour_the_filters() {
		add_filter( 'tribe_tickets_new_views_is_enabled', '__return_false' );
		add_filter( 'tribe_tickets_rsvp_new_views_is_enabled', '__return_false' );

		$this->assertFalse( tribe_tickets_new_views_is_enabled() );
		$this->assertFalse( tribe_tickets_rsvp_new_views_is_enabled() );
	}

	/**
	 * @test
	 */
	public function it_should_still_honour_the_tickets_environment_variable() {
		putenv( 'TRIBE_TICKETS_NEW_VIEWS=0' );

		try {
			$this->assertFalse( tribe_tickets_new_views_is_enabled() );
		} finally {
			putenv( 'TRIBE_TICKETS_NEW_VIEWS' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_still_honour_the_rsvp_environment_variable() {
		putenv( 'TRIBE_TICKETS_RSVP_NEW_VIEWS=0' );

		try {
			$this->assertFalse( tribe_tickets_rsvp_new_views_is_enabled() );
		} finally {
			putenv( 'TRIBE_TICKETS_RSVP_NEW_VIEWS' );
		}
	}

	/**
	 * The options have no readers left inside the plugin, so the stored type is the whole point of
	 * writing them: anything outside reading them may well compare strictly.
	 *
	 * @test
	 */
	public function it_should_store_the_views_as_true_booleans() {
		$this->given_an_old_install_with_the_views_turned_off();

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->force_new_views();

		$this->assertSame( true, tribe_get_option( 'tickets_use_new_views' ) );
		$this->assertSame( true, tribe_get_option( 'tickets_rsvp_use_new_views' ) );
	}

	/**
	 * The migration has to run off the version bump itself.
	 *
	 * The fixture is the case that matters and the one a stale version key gets wrong: a site fully
	 * up to date on the current release, taking the release that carries the migration. A version
	 * key at or below what the site already recorded is skipped, so only a callback that runs on
	 * every bump reaches it.
	 *
	 * @test
	 */
	public function it_should_turn_the_views_on_when_a_current_site_takes_the_next_release() {
		$this->given_an_old_install_with_the_views_turned_off();
		tribe_update_option( 'event-tickets-schema-version', Tribe__Tickets__Main::VERSION );

		$next_release = Tribe__Tickets__Main::VERSION . '.1';
		$updater      = new Tribe__Tickets__Updater( $next_release );

		$this->assertTrue( $updater->update_required(), 'A site behind the running version has to be offered the updates.' );

		$updater->do_updates();

		$this->assertNotFalse( has_action( 'wp_loaded', [ $updater, 'force_new_views' ] ), 'The migration has to be deferred, not skipped.' );

		do_action( 'wp_loaded' );

		$this->assertSame( true, tribe_get_option( 'tickets_use_new_views' ) );
		$this->assertSame( true, tribe_get_option( 'tickets_rsvp_use_new_views' ) );
	}

	/**
	 * A site that upgrades again later must not have anything to do.
	 *
	 * @test
	 */
	public function it_should_leave_a_site_that_is_already_on_the_new_views_alone() {
		tribe_update_option( 'tickets_use_new_views', true );
		tribe_update_option( 'tickets_rsvp_use_new_views', true );

		$writes = 0;
		add_filter(
			'tribe-events-save-options',
			static function ( $options ) use ( &$writes ) {
				++$writes;

				return $options;
			}
		);

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->force_new_views();

		$this->assertSame( 0, $writes );
		$this->assertFalse( (bool) tribe_get_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON ) );
	}

	/**
	 * Only a site the upgrade actually switched gets told about it.
	 *
	 * @test
	 */
	public function it_should_record_that_the_site_was_switched() {
		$this->given_an_old_install_with_the_views_turned_off();

		$this->assertFalse( (bool) tribe_get_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON ) );

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->force_new_views();

		$this->assertTrue( (bool) tribe_get_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_offer_the_upgrade_notice_to_a_site_that_was_not_switched() {
		tribe_update_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON, false );

		$this->assertFalse( tribe( Notice_New_Views_Upgrade::class )->should_display() );
	}

	/**
	 * The notice explains that a setting was taken away, which means nothing to someone who could
	 * never reach it.
	 *
	 * @test
	 */
	public function it_should_not_offer_the_upgrade_notice_to_a_user_who_cannot_change_settings() {
		tribe_update_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON, true );

		$notice = tribe( Notice_New_Views_Upgrade::class );

		/*
		 * Put a qualifying screen in place first, so the capability is the only thing left that can
		 * decide the answer. Setting a screen fires `current_screen`, which the guided setup listens
		 * on to consume the activation flag and redirect, and that redirect ends in tribe_exit().
		 */
		delete_transient( '_tribe_events_activation_redirect' );
		set_current_screen( 'tribe_events_page_tec-tickets-settings' );

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->assertTrue( $notice->should_display(), 'An administrator on a Tickets screen should see it.' );

		wp_set_current_user( static::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->assertFalse( $notice->should_display(), 'The same screen, without the capability, should not.' );
	}

	/**
	 * The options are written for readers outside the plugin, so a site the filter holds off has to
	 * store what it renders rather than keep a stale true.
	 *
	 * @test
	 */
	public function it_should_store_false_for_a_site_the_filter_holds_off() {
		tribe_update_option( 'tickets_use_new_views', true );
		tribe_update_option( 'tickets_rsvp_use_new_views', true );

		add_filter( 'tribe_tickets_new_views_is_enabled', '__return_false' );
		add_filter( 'tribe_tickets_rsvp_new_views_is_enabled', '__return_false' );

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->force_new_views();

		$this->assertSame( false, tribe_get_option( 'tickets_use_new_views' ) );
		$this->assertSame( false, tribe_get_option( 'tickets_rsvp_use_new_views' ) );
		$this->assertFalse( (bool) tribe_get_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON ) );
	}

	/**
	 * Updates run on `init` at priority 0, so reading the flags there sees none of the filters a
	 * theme or plugin registers on `init` itself.
	 *
	 * @test
	 */
	public function it_should_not_read_the_flags_before_the_filters_are_registered() {
		$this->given_an_old_install_with_the_views_turned_off();

		$updater = new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION );
		$updater->migrate_force_new_views();

		$this->assertSame( false, tribe_get_option( 'tickets_use_new_views' ), 'Nothing may be written while the migration is still scheduled.' );

		// The window the deferral exists for: registered after updates ran, before the views render.
		add_filter( 'tribe_tickets_new_views_is_enabled', '__return_false' );
		add_filter( 'tribe_tickets_rsvp_new_views_is_enabled', '__return_false' );

		$updater->force_new_views();

		$this->assertSame( false, tribe_get_option( 'tickets_use_new_views' ) );
		$this->assertSame( false, tribe_get_option( 'tickets_rsvp_use_new_views' ) );
		$this->assertFalse( (bool) tribe_get_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON ), 'A site still rendering the old views must not be told it was switched.' );
	}

	/**
	 * Puts the site in the state that used to show the settings toggles: an install old enough to
	 * predate the new views, with both of them turned off.
	 */
	protected function given_an_old_install_with_the_views_turned_off(): void {
		tribe_update_option( 'previous_event_tickets_versions', [ '4.9.2' ] );
		tribe_update_option( 'tickets_use_new_views', false );
		tribe_update_option( 'tickets_rsvp_use_new_views', false );

		$this->assertTrue( tribe_installed_before( Tribe__Tickets__Main::class, '5.0.3' ), 'The fixture has to read as an old install.' );
	}
}
