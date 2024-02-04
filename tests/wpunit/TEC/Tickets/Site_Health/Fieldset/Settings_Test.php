<?php

namespace TEC\Tickets\Site_Health\Fieldset;

use Codeception\TestCase\WPTestCase;

/**
 * Class Commerce_Test
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Site_Health\Fieldset
 */
class Settings_Test extends WPTestCase {
	/**
	 * @test
	 */
	public function should_be_able_to_instantiate(): void {
		$fieldset = new Settings();
		$this->assertInstanceOf( Settings::class, $fieldset );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Settings::get_post_types_enabled
	 */
	public function should_return_post_types_from_settings_comma_separated(): void {
		$fieldset = new Settings();

		tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'developers' ] );
		$this->assertEquals( 'post, developers', $fieldset->get_post_types_enabled() );
		tribe_remove_option( 'ticket-enabled-post-types' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Settings::get_post_types_enabled
	 */
	public function should_return_post_types_from_settings_comma_separated_skipping_empty_values(): void {
		$fieldset = new Settings();

		tribe_update_option( 'ticket-enabled-post-types', [ 'post', '', 'developers', null, 'test' ] );
		$this->assertEquals( 'post, developers, test', $fieldset->get_post_types_enabled() );
		tribe_remove_option( 'ticket-enabled-post-types' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Settings::get_is_login_required_for_tickets
	 */
	public function should_return_yes_when_tickets_have_login_enabled(): void {
		$fieldset = new Settings();

		tribe_update_option( 'ticket-authentication-requirements', [ 'event-tickets_all' ] );
		$this->assertEquals( 'yes', $fieldset->get_is_login_required_for_tickets() );
		tribe_remove_option( 'ticket-authentication-requirements' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Settings::get_is_login_required_for_tickets
	 */
	public function should_return_no_when_tickets_have_login_enabled(): void {
		$fieldset = new Settings();

		tribe_update_option( 'ticket-authentication-requirements', [ 'developer' ] );
		$this->assertEquals( 'no', $fieldset->get_is_login_required_for_tickets() );
		tribe_remove_option( 'ticket-authentication-requirements' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Settings::get_is_login_required_for_rsvp
	 */
	public function should_return_yes_when_rsvp_have_login_enabled(): void {
		$fieldset = new Settings();

		tribe_update_option( 'ticket-authentication-requirements', [ 'event-tickets_rsvp' ] );
		$this->assertEquals( 'yes', $fieldset->get_is_login_required_for_rsvp() );
		tribe_remove_option( 'ticket-authentication-requirements' );
	}

	/**
	 * @test
	 * @covers \TEC\Tickets\Site_Health\Fieldset\Settings::get_is_login_required_for_rsvp
	 */
	public function should_return_no_when_rsvp_have_login_enabled(): void {
		$fieldset = new Settings();

		tribe_update_option( 'ticket-authentication-requirements', [ 'developer' ] );
		$this->assertEquals( 'no', $fieldset->get_is_login_required_for_rsvp() );
		tribe_remove_option( 'ticket-authentication-requirements' );
	}
}