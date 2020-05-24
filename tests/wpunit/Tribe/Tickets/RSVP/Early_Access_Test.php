<?php

namespace Tribe\Tickets\RSVP;

class Early_Access_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/** @var Early_Access */
	private $early_access;

	private $original_wp_scripts;
	private $original_wp_styles;

	public function setUp() {
		// Before...
		parent::setUp();

		// Your set up methods here.
		global $wp_scripts;
		global $wp_styles;

		if ( is_null( $this->original_wp_scripts ) ) {
			$this->original_wp_scripts = clone $wp_scripts;
		}

		if ( is_null( $this->original_wp_styles ) ) {
			$this->original_wp_styles = clone $wp_styles;
		}

		$this->early_access = clone tribe( Early_Access::class );
	}

	public function tearDown() {
		// Your tear down methods here.
		global $wp_scripts;
		global $wp_styles;

		$wp_scripts = $this->original_wp_scripts;
		$wp_styles  = $this->original_wp_styles;

		// Then...
		parent::tearDown();
	}

	/** @test */
	public function should_deregister_rsvp_assets_if_early_access() {
		$this->early_access->set_rsvp_early_access( true );

		global $wp_scripts;
		global $wp_styles;

		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-css', $wp_styles->registered );
		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-js', $wp_scripts->registered );

		$this->early_access->maybe_deregister_rsvp_assets();

		$this->assertArrayNotHasKey( 'event-tickets-tickets-rsvp-css', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'event-tickets-tickets-rsvp-js', $wp_scripts->registered );
	}

	/** @test */
	public function should_not_deregister_rsvp_assets_if_not_early_access() {
		$this->early_access->set_rsvp_early_access( false );

		global $wp_scripts;
		global $wp_styles;

		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-css', $wp_styles->registered );
		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-js', $wp_scripts->registered );

		$this->early_access->maybe_deregister_rsvp_assets();

		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-css', $wp_styles->registered );
		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-js', $wp_scripts->registered );
	}

	/** @test */
	public function should_register_rsvp_early_access_assets_if_early_access() {
		$this->early_access->set_rsvp_early_access( true );

		tribe( 'assets' )->remove( 'event-tickets-rsvp-early-access-styles' );
		tribe( 'assets' )->remove( 'event-tickets-rsvp-early-access-scripts' );

		$this->early_access->maybe_register_early_access_assets();

		$this->assertTrue( tribe( 'assets' )->exists( 'event-tickets-rsvp-early-access-styles' ) );
		$this->assertTrue( tribe( 'assets' )->exists( 'event-tickets-rsvp-early-access-scripts' ) );
	}

	/** @test */
	public function should_not_register_rsvp_early_access_assets_if_not_early_access() {
		$this->early_access->set_rsvp_early_access( false );

		tribe( 'assets' )->remove( 'event-tickets-rsvp-early-access-styles' );
		tribe( 'assets' )->remove( 'event-tickets-rsvp-early-access-scripts' );

		$this->early_access->maybe_register_early_access_assets();

		$this->assertFalse( tribe( 'assets' )->exists( 'event-tickets-rsvp-early-access-styles' ) );
		$this->assertFalse( tribe( 'assets' )->exists( 'event-tickets-rsvp-early-access-scripts' ) );
	}

	/** @test */
	public function should_not_change_rsvp_template_if_not_early_access() {
		$this->early_access->set_rsvp_early_access( false );

		$template = $this->early_access->maybe_override_template( 'foo/rsvp/bar/rsvp.php' );

		$this->assertEquals( 'foo/rsvp/bar/rsvp.php', $template );
	}

	/** @test */
	public function should_change_rsvp_template_if_early_access() {
		$this->early_access->set_rsvp_early_access( true );

		$template = $this->early_access->maybe_override_template( 'foo/rsvp/bar/rsvp.php' );

		$this->assertEquals( 'foo/rsvp/bar/rsvp-early-access.php', $template );
	}
}
