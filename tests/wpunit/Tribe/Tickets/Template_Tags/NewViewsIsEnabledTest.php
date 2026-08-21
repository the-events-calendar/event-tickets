<?php

namespace Tribe\Tickets;

use Tribe__Settings_Manager;
use Tribe__Tickets__Updater;

/**
 * Class NewViewsIsEnabledTest
 *
 * @package Tribe\Tickets
 *
 * @see     \tribe_tickets_new_views_is_enabled()
 * @see     \tribe_tickets_rsvp_new_views_is_enabled()
 */
class NewViewsIsEnabledTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * Puts the site in the state that used to show the settings toggles: an install old enough to
	 * predate the new views, with both of them turned off.
	 */
	protected function given_an_old_install_with_the_views_turned_off() {
		Tribe__Settings_Manager::set_option( 'previous_event_tickets_versions', [ '4.9.2' ] );
		tribe_update_option( 'tickets_use_new_views', false );
		tribe_update_option( 'tickets_rsvp_use_new_views', false );
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
	public function it_should_still_honour_the_environment_variables() {
		putenv( 'TRIBE_TICKETS_NEW_VIEWS=0' );
		putenv( 'TRIBE_TICKETS_RSVP_NEW_VIEWS=0' );

		try {
			$this->assertFalse( tribe_tickets_new_views_is_enabled() );
			$this->assertFalse( tribe_tickets_rsvp_new_views_is_enabled() );
		} finally {
			putenv( 'TRIBE_TICKETS_NEW_VIEWS' );
			putenv( 'TRIBE_TICKETS_RSVP_NEW_VIEWS' );
		}
	}

	/**
	 * @test
	 */
	public function it_should_turn_the_views_on_for_a_site_that_had_them_off() {
		$this->given_an_old_install_with_the_views_turned_off();

		( new Tribe__Tickets__Updater( \Tribe__Tickets__Main::VERSION ) )->migrate_5_29_3_force_new_views();

		$this->assertTrue( (bool) tribe_get_option( 'tickets_use_new_views' ) );
		$this->assertTrue( (bool) tribe_get_option( 'tickets_rsvp_use_new_views' ) );
	}

	/**
	 * The toggles used to be added for any install older than 5.0.3, which is the whole of the bug.
	 *
	 * @test
	 */
	public function it_should_not_offer_the_settings_to_an_old_install() {
		$this->given_an_old_install_with_the_views_turned_off();

		$fields = apply_filters( 'tribe_tickets_settings_tab_fields', [ 'tribe-form-content-end' => [] ] );

		$this->assertArrayNotHasKey( 'tickets_use_new_views', $fields );
		$this->assertArrayNotHasKey( 'tickets_rsvp_use_new_views', $fields );
	}
}
