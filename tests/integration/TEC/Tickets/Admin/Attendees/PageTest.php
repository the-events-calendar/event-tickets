<?php

namespace TEC\Tickets\Admin\Attendees;

use Tribe\Admin\Pages;
use Tribe__Tickets__Attendees as Attendees;
use Tribe__Tickets__Attendees_Table as Attendees_Table;

/**
 * @covers \TEC\Tickets\Admin\Attendees\Page
 *
 * Regression coverage for SMTNC-1439: opening the Attendees admin page fataled
 * with "Call to a member function prepare_items() on null" on sites running a
 * translated admin locale. The page wired Tribe__Tickets__Attendees::screen_setup()
 * - the only thing that builds $attendees->attendees_table - to a *hardcoded*
 * English hook suffix ("tickets_page_tec-tickets-attendees"). WordPress derives
 * the real hook suffix from the translatable parent "Tickets" menu title, so under
 * any other locale the load hook never fired, the table stayed null, and
 * admin-views/attendees/attendees-table.php:12 fataled.
 */
class PageTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Page
	 */
	protected $page;

	public function setUp(): void {
		parent::setUp();

		// register_page() runs add_submenu_page(), which lives in the admin plugin API.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// The page capability defaults to manage_options; act as an admin so it registers.
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );

		tribe_singleton( 'admin.pages', new Pages() );

		$this->page = new Page();

		// Start each test from a clean admin-menu state.
		$this->reset_admin_menu_globals();

		// Reset the static suffix to its English default so we observe the value the
		// page actually captures during registration rather than a previous test's.
		Page::$hook_suffix = 'tickets_page_tec-tickets-attendees';
	}

	public function tearDown(): void {
		$this->reset_admin_menu_globals();
		Page::$hook_suffix = 'tickets_page_tec-tickets-attendees';

		parent::tearDown();
	}

	/**
	 * Clears the WordPress admin-menu registries so each test registers the parent
	 * "Tickets" menu (and its hook suffix) from scratch.
	 */
	protected function reset_admin_menu_globals(): void {
		$GLOBALS['menu']              = [];
		$GLOBALS['submenu']           = [];
		$GLOBALS['admin_page_hooks']  = [];
		$GLOBALS['_registered_pages'] = [];
		$GLOBALS['_parent_pages']     = [];
	}

	/**
	 * Registers the top-level "Tickets" parent menu under the given (already
	 * translated) title, mirroring Tribe\Admin\Settings::add_admin_pages(). This is
	 * what seeds $admin_page_hooks['tec-tickets'] = sanitize_title( $title ), from
	 * which WordPress derives every child page's hook suffix.
	 *
	 * @param string $translated_title The translated "Tickets" menu label.
	 */
	protected function register_parent_menu_with_title( string $translated_title ): void {
		add_menu_page(
			$translated_title,
			$translated_title,
			'manage_options',
			Page::$parent_slug,
			'__return_null'
		);
	}

	/**
	 * @return \Generator<string,array{0:string,1:string}>
	 */
	public function locale_menu_title_provider(): \Generator {
		yield 'English (Tickets)' => [ 'Tickets', 'tickets_page_tec-tickets-attendees' ];
		yield 'Portuguese (Ingressos)' => [ 'Ingressos', 'ingressos_page_tec-tickets-attendees' ];
		yield 'French (Billets)' => [ 'Billets', 'billets_page_tec-tickets-attendees' ];
	}

	/**
	 * @test
	 * @dataProvider locale_menu_title_provider
	 */
	public function it_should_hook_screen_setup_to_the_real_locale_aware_hook_suffix( string $menu_title, string $expected_hook_suffix ): void {
		$this->register_parent_menu_with_title( $menu_title );

		$this->page->add_tec_tickets_attendees_page();

		$this->assertSame(
			$expected_hook_suffix,
			Page::$hook_suffix,
			'The page should capture the hook suffix WordPress actually generated for the (translated) parent menu, not a hardcoded one.'
		);

		$this->assertNotFalse(
			has_action( "load-{$expected_hook_suffix}", [ tribe( 'tickets.attendees' ), 'screen_setup' ] ),
			'screen_setup() must be hooked to the page\'s real load hook so the attendees table gets initialized before render.'
		);
	}

	/**
	 * @test
	 *
	 * Pins the actual regression: under a translated locale the page must NOT be
	 * relying solely on the hardcoded English hook, which never fires.
	 */
	public function it_should_not_leave_screen_setup_on_only_the_hardcoded_english_hook_under_a_translated_locale(): void {
		$this->register_parent_menu_with_title( 'Ingressos' );

		$this->page->add_tec_tickets_attendees_page();

		$this->assertFalse(
			has_action( 'load-tickets_page_tec-tickets-attendees', [ tribe( 'tickets.attendees' ), 'screen_setup' ] ),
			'Under a translated locale the English hook is never fired by WordPress, so screen_setup wired only to it would never run.'
		);
	}

	/**
	 * @test
	 *
	 * Once screen_setup() runs (it is now correctly hooked), the attendees table is
	 * an object, so attendees-table.php:12 can no longer call prepare_items() on null.
	 */
	public function it_should_populate_the_attendees_table_when_the_load_hook_fires(): void {
		$this->register_parent_menu_with_title( 'Ingressos' );
		$this->page->add_tec_tickets_attendees_page();

		/** @var Attendees $attendees */
		$attendees = tribe( 'tickets.attendees' );
		$attendees->attendees_table = null;

		// Mimic an actual request to the attendees page so screen_setup()'s guard passes.
		$_GET['page'] = Page::$slug;

		do_action( 'load-' . Page::$hook_suffix );

		unset( $_GET['page'] );

		$this->assertInstanceOf(
			Attendees_Table::class,
			$attendees->attendees_table,
			'Firing the page load hook must build the attendees table so the template never dereferences null.'
		);
	}

	/**
	 * @test
	 *
	 * Defensive: when the page is not registered (e.g. the user lacks the capability)
	 * the method must bail without hooking anything or mutating the suffix.
	 */
	public function it_should_bail_when_the_page_is_not_registered(): void {
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$this->register_parent_menu_with_title( 'Ingressos' );

		$this->page->add_tec_tickets_attendees_page();

		$this->assertSame(
			'tickets_page_tec-tickets-attendees',
			Page::$hook_suffix,
			'A failed registration should leave the default suffix untouched.'
		);
		$this->assertFalse(
			has_action( 'load-ingressos_page_tec-tickets-attendees', [ tribe( 'tickets.attendees' ), 'screen_setup' ] ),
			'No screen_setup hook should be added when the page is not registered.'
		);
	}
}
