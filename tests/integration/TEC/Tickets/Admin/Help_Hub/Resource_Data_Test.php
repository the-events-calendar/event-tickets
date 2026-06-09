<?php

namespace TEC\Tickets\Admin\Help_Hub;

use Codeception\TestCase\WPTestCase;

/**
 * Ensures the Help Hub page detection and asset gating stay locale-independent so the page keeps
 * loading its assets when the menu title is translated (and the screen id is no longer `tickets_page_*`).
 *
 * @see ET_Hub_Resource_Data::is_help_hub_page()
 * @see ET_Hub_Resource_Data::add_help_hub_pages()
 */
class Resource_Data_Test extends WPTestCase {

	/**
	 * @after
	 */
	public function reset_request(): void {
		$_GET = [];
		unset( $GLOBALS['current_screen'] );
	}

	/**
	 * @return ET_Hub_Resource_Data
	 */
	private function make_instance(): ET_Hub_Resource_Data {
		return new ET_Hub_Resource_Data();
	}

	/**
	 * @test
	 */
	public function it_should_detect_the_help_hub_page_by_slug_in_any_language(): void {
		$instance = $this->make_instance();

		$_GET['page'] = 'tec-tickets-help-hub';
		$this->assertTrue( $instance->is_help_hub_page() );

		$_GET['page'] = 'some-other-page';
		$this->assertFalse( $instance->is_help_hub_page() );
	}

	/**
	 * @test
	 */
	public function it_should_allow_the_translated_screen_id_on_the_help_hub_page(): void {
		$_GET['page'] = 'tec-tickets-help-hub';
		// German: "Tickets" -> "Karten", so the screen id is prefixed with `karten_page_`.
		set_current_screen( 'karten_page_tec-tickets-help-hub' );

		$pages = $this->make_instance()->add_help_hub_pages( [] );

		// The canonical English id and the live (translated) screen id must both be present.
		$this->assertContains( 'tickets_page_tec-tickets-help-hub', $pages );
		$this->assertContains( 'karten_page_tec-tickets-help-hub', $pages );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_the_current_screen_when_off_the_help_hub_page(): void {
		$_GET['page'] = 'some-other-page';
		set_current_screen( 'karten_page_tec-tickets-help-hub' );

		$pages = $this->make_instance()->add_help_hub_pages( [] );

		$this->assertNotContains( 'karten_page_tec-tickets-help-hub', $pages );
	}
}
