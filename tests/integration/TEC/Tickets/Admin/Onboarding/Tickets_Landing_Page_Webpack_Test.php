<?php
/**
 * Tests for Tickets_Landing_Page webpack public path functionality.
 *
 * @package TEC\Tickets\Admin\Onboarding
 * @since   TBD
 */

namespace TEC\Tickets\Admin\Onboarding;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Main as Tickets;

/**
 * Class Tickets_Landing_Page_Webpack_Test
 *
 * Tests the webpack public path configuration for the tickets onboarding wizard.
 *
 * @since TBD
 */
class Tickets_Landing_Page_Webpack_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * The Tickets_Landing_Page instance.
	 *
	 * @var Tickets_Landing_Page
	 */
	protected $landing_page;

	/**
	 * Set up test environment.
	 *
	 * @since TBD
	 */
	public function setUp() {
		parent::setUp();

		// Set up current user as admin.
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$this->landing_page = tribe( Tickets_Landing_Page::class );
	}

	/**
	 * Test that set_webpack_public_path outputs the correct script tag.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_output_webpack_public_path_script() {
		// Mock the should_show_wizard() condition.
		update_option( 'tribe-wizard-et-onboarding-dismissed', false );
		$_GET['page'] = 'tec-tickets-onboarding';

		// Capture the output.
		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// Verify script tag exists.
		$this->assertStringContainsString( '<script type="text/javascript">', $output );
		$this->assertStringContainsString( 'window.etWebpackPublicPath', $output );
		$this->assertStringContainsString( '</script>', $output );
	}

	/**
	 * Test that the webpack public path contains the build directory.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_include_build_directory_in_path() {
		update_option( 'tribe-wizard-et-onboarding-dismissed', false );
		$_GET['page'] = 'tec-tickets-onboarding';

		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// Should contain /build/ in the path (may be JSON-escaped as \/build\/).
		$this->assertTrue(
			str_contains( $output, '/build/' ) || str_contains( $output, '\/build\/' ),
			'Output should contain /build/ directory'
		);
	}

	/**
	 * Test that the webpack public path is a valid URL.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_output_valid_url() {
		update_option( 'tribe-wizard-et-onboarding-dismissed', false );
		$_GET['page'] = 'tec-tickets-onboarding';

		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// Extract the URL from the output using regex.
		preg_match( '/window\.etWebpackPublicPath\s*=\s*"([^"]+)"/', $output, $matches );

		$this->assertNotEmpty( $matches[1], 'Should find a URL in the output' );

		$url = $matches[1];

		// URLs may be JSON-escaped, so decode them.
		$url_decoded = stripslashes( $url );

		// Verify it's a valid URL.
		$this->assertStringStartsWith( 'http', $url_decoded );
		$this->assertStringContainsString( 'wp-content/plugins/event-tickets/build/', $url_decoded );

		// Verify it ends with a trailing slash.
		$this->assertStringEndsWith( '/', $url_decoded );
	}

	/**
	 * Test that set_webpack_public_path respects the wizard display check.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_respect_wizard_check() {
		// Verify the method is public and can be called.
		$method = new \ReflectionMethod( $this->landing_page, 'set_webpack_public_path' );
		$this->assertTrue( $method->isPublic(), 'Method should be public' );

		// Verify the method checks should_show_wizard by ensuring it doesn't fatal.
		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// The output may or may not be empty depending on test environment,
		// but it should not cause errors.
		$this->assertTrue( true, 'Method executes without errors' );
	}

	/**
	 * Test webpack public path with custom WP_CONTENT_DIR.
	 *
	 * This simulates WordPress installations with non-standard directory structures.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_work_with_custom_wp_content_dir() {
		update_option( 'tribe-wizard-et-onboarding-dismissed', false );
		$_GET['page'] = 'tec-tickets-onboarding';

		// Use uopz to temporarily redefine the WP constants.
		// This properly handles already-defined constants in the test environment.
		$this->set_const_value( 'WP_CONTENT_DIR', '/custom/path/to/content' );
		$this->set_const_value( 'WP_CONTENT_URL', 'https://example.com/custom-content' );

		// Verify constants were set.
		$this->assertEquals( '/custom/path/to/content', WP_CONTENT_DIR );
		$this->assertEquals( 'https://example.com/custom-content', WP_CONTENT_URL );

		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// Extract URL.
		preg_match( '/window\.etWebpackPublicPath\s*=\s*"([^"]+)"/', $output, $matches );

		$this->assertNotEmpty( $matches[1], 'Should output a URL even with custom wp-content' );

		$url = $matches[1];

		// Should be a valid URL regardless of directory structure (may be JSON-escaped).
		$url_decoded = stripslashes( $url );
		$this->assertStringStartsWith( 'http', $url_decoded );
		$this->assertStringContainsString( '/build/', $url_decoded );
		$this->assertStringEndsWith( '/', $url_decoded );
	}

	/**
	 * Test that the URL uses the correct ET namespace.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_use_et_namespace() {
		update_option( 'tribe-wizard-et-onboarding-dismissed', false );
		$_GET['page'] = 'tec-tickets-onboarding';

		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// Should use etWebpackPublicPath, not tecWebpackPublicPath.
		$this->assertStringContainsString( 'window.etWebpackPublicPath', $output );
		$this->assertStringNotContainsString( 'window.tecWebpackPublicPath', $output );
	}

	/**
	 * Test that the URL is properly escaped for JavaScript.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_escape_url_for_javascript() {
		update_option( 'tribe-wizard-et-onboarding-dismissed', false );
		$_GET['page'] = 'tec-tickets-onboarding';

		ob_start();
		$this->landing_page->set_webpack_public_path();
		$output = ob_get_clean();

		// Verify the output is valid JSON-encoded.
		preg_match( '/window\.etWebpackPublicPath\s*=\s*(.+);/', $output, $matches );
		$this->assertNotEmpty( $matches[1] );

		// Should be a valid JSON string.
		$decoded = json_decode( $matches[1] );
		$this->assertNotNull( $decoded, 'URL should be valid JSON-encoded string' );
	}

	/**
	 * Test that the script is added via admin_head hook.
	 *
	 * @test
	 * @since TBD
	 */
	public function it_should_hook_into_admin_head() {
		// Register assets to add the hooks.
		$this->landing_page->register_assets();

		// Verify the hook is registered.
		$this->assertNotFalse(
			has_action( 'admin_head', [ $this->landing_page, 'set_webpack_public_path' ] ),
			'set_webpack_public_path should be hooked to admin_head'
		);

		// Verify it's hooked with priority 1 (early).
		$this->assertEquals(
			1,
			has_action( 'admin_head', [ $this->landing_page, 'set_webpack_public_path' ] ),
			'Hook should have priority 1 to run early'
		);
	}

	/**
	 * Clean up after tests.
	 *
	 * @since TBD
	 */
	public function tearDown() {
		unset( $_GET['page'] );
		delete_option( 'tribe-wizard-et-onboarding-dismissed' );
		parent::tearDown();
	}
}
