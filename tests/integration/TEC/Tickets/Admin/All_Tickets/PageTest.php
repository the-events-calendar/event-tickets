<?php

namespace TEC\Tickets\Admin\All_Tickets;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use Tribe\Tests\Traits\With_Uopz;

class PageTest extends \Codeception\TestCase\WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * @var \TEC\Tickets\Admin\All_Tickets\Page
	 */
	protected $page;

	public function setUp(): void {
		// before
		parent::setUp();

		$this->page = new Page();
	}

	// test
	public function test_is_on_page() {
		// Not on page.
		$this->assertFalse( $this->page->is_on_page(), 'Should return false when not on page.' );

		// On page.
		$this->set_fn_return( 'get_current_screen',  ( object ) [
			'id' => Page::$slug,
		] );
		$this->assertTrue( $this->page->is_on_page(), 'Should return true when on page.' );
	}

	// test
	public function test_get_url() {
		$this->assertEquals(
			'http://wordpress.test/wp-admin/admin.php?page=' . Page::$slug,
			$this->page->get_url(),
			'Should return regular URL when no arguments are passed.'
		);
		$this->assertEquals(
			'http://wordpress.test/wp-admin/admin.php?page=' . Page::$slug . '&s=some-value&var=some-other-page',
			$this->page->get_url( [
				's'    => 'some-value',
				'var'  => 'some-other-page',
			] ),
			'Should add to query URL when no arguments are passed.'
		);
	}

	// test
	public function test_render_tec_tickets_all_tickets_page() {
		ob_start();
		$this->page->render_tec_tickets_all_tickets_page();
		$actual = ob_get_clean();
		preg_match( '/name=\"_wpnonce\" value=\"([^\"]+)\"/', $actual, $matches );
		$nonce = $matches[1];
		$actual = str_replace( $nonce, 'WP_NONCE', $actual );
		$this->assertMatchesHtmlSnapshot( $actual );
	}
}
