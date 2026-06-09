<?php
namespace Tribe\Tickets\REST\V1;

use Tribe__Tickets__REST__V1__Main as Main;

class MainTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var string
	 */
	protected $site_url;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->site_url = get_option( 'siteurl' );
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Main::class, $sut );
	}

	/**
	 * @test
	 * it should return the right ET REST URL prefix
	 */
	public function it_should_return_the_right_rest_url_prefix() {
		$sut = $this->make_instance();

		$this->assertEquals( 'wp-json/tribe/tickets/v1', $sut->get_url_prefix() );
	}

	/**
	 * @test
	 * it should return the right ET REST URL prefix when non using built-in REST API function
	 */
	public function it_should_return_the_right_et_rest_url_prefix_when_non_using_built_in_rest_api_function() {
		add_filter( 'tribe_tickets_rest_use_builtin', '__return_false' );

		$sut = $this->make_instance();

		$this->assertEquals( 'wp-json/tribe/tickets/v1', $sut->get_url_prefix() );
	}

	/**
	 * @test
	 * it should return the right ET REST URL for a path
	 */
	public function it_should_return_the_right_et_rest_url_for_a_path() {
		$sut = $this->make_instance();

		$expected = str_replace( home_url(), $this->site_url, rest_url( '/tribe/tickets/v1/some/path' ) );
		$this->assertEquals( $expected, $sut->get_url( 'some/path' ) );
	}

	/**
	 * @test
	 * it should return the right ET REST URL for a path when not using built-in functions
	 */
	public function it_should_return_the_right_et_rest_url_for_a_path_when_not_using_built_in_functions() {
		add_filter( 'tribe_tickets_rest_use_builtin', '__return_false' );

		$sut = $this->make_instance();

		$expected = str_replace( home_url(), $this->site_url, rest_url( '/tribe/tickets/v1/some/path' ) );
		$this->assertEquals( $expected, $sut->get_url( 'some/path' ) );
	}

	/**
	 * @test
	 * it should return the right ET REST URL for a path when using built-in functions and permalinks
	 */
	public function it_should_return_the_right_et_rest_url_for_a_path_when_using_built_in_functions_and_permalinks() {
		$this->set_permalinks();

		$sut = $this->make_instance();

		$expected = str_replace( home_url(), $this->site_url, rest_url( '/tribe/tickets/v1/some/path' ) );
		$this->assertEquals( $expected, $sut->get_url( 'some/path' ) );
	}

	/**
	 * @test
	 * it should return the right ET REST URL for a path when not using built-in functions and permalinks
	 */
	public function it_should_return_the_right_et_rest_url_for_a_path_when_not_using_built_in_functions_and_permalinks() {
		$this->set_permalinks();
		add_filter( 'tribe_tickets_rest_use_builtin', '__return_false' );

		$sut = $this->make_instance();

		$this->assertEquals( $this->site_url . '/wp-json/tribe/tickets/v1/some/path', $sut->get_url( 'some/path' ) );
	}

	/**
	 * @test
	 * it should include hidden-from-listings events for privileged requests
	 */
	public function it_should_include_hidden_events_for_privileged_requests() {
		// The `tribe_manage_attendees` cap grants manage access (works on single and multisite).
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		( new \WP_User( $admin_id ) )->add_cap( 'tribe_manage_attendees' );
		wp_set_current_user( $admin_id );

		$sut     = $this->make_instance();
		$request = new \WP_REST_Request( 'GET', '/tribe/events/v1/events' );

		$args = $sut->parse_events_rest_args( [], [], $request );

		$this->assertArrayHasKey( 'hide_upcoming', $args );
		$this->assertFalse( $args['hide_upcoming'] );
	}

	/**
	 * @test
	 * it should not include hidden-from-listings events for unprivileged requests
	 */
	public function it_should_not_include_hidden_events_for_unprivileged_requests() {
		// Logged-out request with no API key configured: no manage access.
		wp_set_current_user( 0 );
		tribe_update_option( 'tickets-plus-qr-options-api-key', '' );

		$sut     = $this->make_instance();
		$request = new \WP_REST_Request( 'GET', '/tribe/events/v1/events' );

		$args = $sut->parse_events_rest_args( [], [], $request );

		$this->assertArrayNotHasKey( 'hide_upcoming', $args );
	}

	/**
	 * @test
	 * it should let the filter override the include-hidden decision
	 */
	public function it_should_let_the_filter_override_the_include_hidden_decision() {
		$admin_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		( new \WP_User( $admin_id ) )->add_cap( 'tribe_manage_attendees' );
		wp_set_current_user( $admin_id );

		add_filter( 'tec_tickets_rest_events_archive_include_hidden_from_listings', '__return_false' );

		$sut     = $this->make_instance();
		$request = new \WP_REST_Request( 'GET', '/tribe/events/v1/events' );

		$args = $sut->parse_events_rest_args( [], [], $request );

		remove_filter( 'tec_tickets_rest_events_archive_include_hidden_from_listings', '__return_false' );

		$this->assertArrayNotHasKey( 'hide_upcoming', $args );
	}

	/**
	 * @return Main
	 */
	protected function make_instance() {
		return new Main();
	}

	protected function set_permalinks() {
		/** @var \WP_Rewrite */
		global $wp_rewrite;
		$structure = '/%postname%/';
		$wp_rewrite->set_permalink_structure( $structure );
		update_option( 'permalink_structure', $structure );
		$wp_rewrite->init();
		$wp_rewrite->flush_rules( true );
	}
}