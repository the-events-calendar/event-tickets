<?php

namespace TEC\Tickets\Test\Integration\Settings;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Admin\Settings;

/**
 * Ensures the Tickets admin pages keep a locale-independent body class so the admin CSS (which is
 * scoped to `.tickets_page_tec-tickets-*`) keeps matching when the menu title is translated.
 *
 * @see Settings::filter_admin_body_class()
 */
class Admin_Body_Class_Test extends WPTestCase {

	/**
	 * @after
	 */
	public function reset_screen(): void {
		unset( $GLOBALS['current_screen'] );
	}

	/**
	 * @return Settings
	 */
	private function make_instance(): Settings {
		return new Settings();
	}

	/**
	 * @test
	 */
	public function it_should_add_the_canonical_class_when_the_menu_title_is_translated(): void {
		// Italian: "Tickets" -> "Biglietti", so the screen id is prefixed with `biglietti_page_`.
		set_current_screen( 'biglietti_page_tec-tickets-settings' );

		$classes = $this->make_instance()->filter_admin_body_class( 'wp-admin biglietti_page_tec-tickets-settings' );

		$this->assertContains( 'tickets_page_tec-tickets-settings', explode( ' ', $classes ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_duplicate_the_class_on_an_english_install(): void {
		set_current_screen( 'tickets_page_tec-tickets-settings' );

		$classes = $this->make_instance()->filter_admin_body_class( 'wp-admin tickets_page_tec-tickets-settings' );

		$occurrences = array_filter(
			explode( ' ', $classes ),
			static fn( $class ) => 'tickets_page_tec-tickets-settings' === $class
		);

		$this->assertCount( 1, $occurrences );
	}

	/**
	 * @test
	 */
	public function it_should_leave_non_tickets_screens_untouched(): void {
		set_current_screen( 'edit-post' );

		$classes = $this->make_instance()->filter_admin_body_class( 'wp-admin edit-post' );

		$this->assertSame( 'wp-admin edit-post', $classes );
	}
}
