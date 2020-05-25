<?php

namespace Tribe\Tickets\RSVP\Early_Access;

class Early_Access_Assets_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WpunitTester
	 */
	protected $tester;

	/** @var Assets */
	private $assets;

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

		$this->assets       = clone tribe( Assets::class );
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
		add_filter( 'tribe_tickets_is_rsvp_early_access', function() {
			return true;
		} );

		global $wp_scripts;
		global $wp_styles;

		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-css', $wp_styles->registered );
		$this->assertArrayHasKey( 'event-tickets-tickets-rsvp-js', $wp_scripts->registered );

		$this->assets->deregister_rsvp_assets();

		$this->assertArrayNotHasKey( 'event-tickets-tickets-rsvp-css', $wp_styles->registered );
		$this->assertArrayNotHasKey( 'event-tickets-tickets-rsvp-js', $wp_scripts->registered );
	}

	/** @test */
	public function should_register_rsvp_early_access_assets_if_early_access() {
		add_filter( 'tribe_tickets_is_rsvp_early_access', function() {
			return true;
		} );

		tribe( 'assets' )->remove( 'event-tickets-rsvp-early-access-styles' );
		tribe( 'assets' )->remove( 'event-tickets-rsvp-early-access-scripts' );

		$this->assets->register_early_access_assets();

		$this->assertTrue( tribe( 'assets' )->exists( 'event-tickets-rsvp-early-access-styles' ) );
		$this->assertTrue( tribe( 'assets' )->exists( 'event-tickets-rsvp-early-access-scripts' ) );
	}

}
