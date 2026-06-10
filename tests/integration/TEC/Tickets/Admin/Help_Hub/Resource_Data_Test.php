<?php

namespace TEC\Tickets\Admin\Help_Hub;

use Codeception\TestCase\WPTestCase;

/**
 * Ensures the Help Hub page detection stays locale-independent so the page keeps loading its assets
 * when the menu title is translated (and the screen id is no longer `tickets_page_*`).
 *
 * The page is detected by its URL `page` slug, which is locale-independent, and the page hook is
 * resolved from WordPress core via get_plugin_page_hook(), falling back to the canonical English id.
 *
 * @see ET_Hub_Resource_Data::is_help_hub_page()
 * @see ET_Hub_Resource_Data::add_help_hub_pages()
 * @see ET_Hub_Resource_Data::get_help_hub_id()
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

		$_GET['page'] = ET_Hub_Resource_Data::HELP_HUB_SLUG;
		$this->assertTrue( $instance->is_help_hub_page() );

		$_GET['page'] = 'some-other-page';
		$this->assertFalse( $instance->is_help_hub_page() );
	}

	/**
	 * @test
	 */
	public function it_should_not_detect_the_help_hub_page_without_a_page_request(): void {
		$this->assertFalse( $this->make_instance()->is_help_hub_page() );
	}

	/**
	 * @test
	 */
	public function it_should_fall_back_to_the_canonical_id_when_the_page_hook_is_unresolved(): void {
		$pages = $this->make_instance()->add_help_hub_pages( [] );

		$this->assertContains( ET_Hub_Resource_Data::HELP_HUB_PAGE_ID, $pages );
	}
}
