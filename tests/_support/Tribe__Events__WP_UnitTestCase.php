<?php

class Tribe__Events__WP_UnitTestCase extends \Codeception\TestCase\WPTestCase {

	// array of deprecated files we expect to encounter
	protected $expected_deprecated_file = [];

	// array of deprecated files we caught encounter
	protected $caught_deprecated_file = [];

	public function setUp() {
		// reload capabilities after install, see https://core.trac.wordpress.org/ticket/28374
		$GLOBALS['wp_roles'] = new WP_Roles;
		parent::setUp();
	}

	public function expectDeprecated() {
		parent::expectDeprecated();

		add_action( 'deprecated_file_included', array( $this, 'deprecated_file_run' ) );
		add_action( 'deprecated_file_trigger_error', '__return_false' );
	}

	public function deprecated_file_run( $file ) {
		$file = str_replace( Tribe__Tickets__Main::instance()->plugin_path, '', $file );

		if ( in_array( $file, $this->caught_deprecated_file ) ) {
			return;
		}

		$this->caught_deprecated_file[] = $file;
	}

	public function expectedDeprecated() {
		$not_caught_deprecated_file = array_diff( $this->expected_deprecated_file, $this->caught_deprecated_file );
		foreach ( $not_caught_deprecated_file as $not_caught ) {
			$this->fail( "Failed to assert that $not_caught triggered a deprecated file notice" );
		}

		$unexpected_deprecated_file = array_diff( $this->caught_deprecated_file, $this->expected_deprecated_file );
		foreach ( $unexpected_deprecated_file as $unexpected ) {
			$this->fail( "Unexpected deprecated file: $unexpected" );
		}

		parent::expectedDeprecated();
	}
}
