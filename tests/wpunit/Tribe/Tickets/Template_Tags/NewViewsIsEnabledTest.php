<?php

namespace Tribe\Tickets;

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
		foreach ( [ 'previous_event_tickets_versions', 'tickets_use_new_views', 'tickets_rsvp_use_new_views', 'event-tickets-schema-version' ] as $option ) {
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
	 * Puts the site in the state that used to show the settings toggles: an install old enough to
	 * predate the new views, with both of them turned off.
	 */
	protected function given_an_old_install_with_the_views_turned_off(): void {
		tribe_update_option( 'previous_event_tickets_versions', [ '4.9.2' ] );
		tribe_update_option( 'tickets_use_new_views', false );
		tribe_update_option( 'tickets_rsvp_use_new_views', false );

		$this->assertTrue( tribe_installed_before( Tribe__Tickets__Main::class, '5.0.3' ), 'The fixture has to read as an old install.' );
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

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->migrate_force_new_views();

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

		( new Tribe__Tickets__Updater( Tribe__Tickets__Main::VERSION ) )->migrate_force_new_views();

		$this->assertSame( 0, $writes );
	}
}
