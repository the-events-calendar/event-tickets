<?php
/**
 * Tests for Help Hub Templates
 *
 * @since   TBD
 * @package TEC\Common\Admin\Help_Hub
 */

namespace TEC\Tickets\Admin\Help_Hub;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Admin\Help_Hub\Hub;
use TEC\Common\Configuration\Configuration;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Template;

/**
 * Class TemplateTest
 *
 * @since   TBD
 * @package TEC\Tickets\Admin\Help_Hub
 */
class Template_Test extends WPTestCase {
	use SnapshotAssertions;
	use With_Uopz;

	/**
	 * @var Resource_Data_Mock
	 */
	protected $mock_data;

	/**
	 * @var Hub
	 */
	protected $hub;

	/**
	 * Set up the test environment.
	 *
	 * @before
	 */
	public function setup_enviroment(): void {
		// Register the section builders in the container
		// Initialize dependencies using tribe()
		$this->mock_data = new ET_Hub_Resource_Data();

		// Instantiate necessary dependencies for the Help Hub
		$template = tribe( Tribe__Template::class );
		$config   = tribe( Configuration::class );

		// Instantiate the Hub instance with all dependencies
		$this->hub = new Hub( $this->mock_data, $config, $template );
		$this->set_fn_return( 'tribe_resource_url', 'https://example.com/' );
	}

	/**
	 * Test that the Help Hub initialization works correctly.
	 *
	 * @test
	 */
	public function initialization_works(): void {
		// Initialize the mock data
		$this->mock_data->initialize();

		// Verify that hooks are properly registered
		$this->assertTrue( has_filter( 'tec_help_hub_body_classes' ) );
		$this->assertTrue( has_filter( 'tec_help_hub_resources_description' ) );
		$this->assertTrue( has_filter( 'tec_help_hub_support_title' ) );

		// Verify that the hooks return expected values
		$body_classes = apply_filters( 'tec_help_hub_body_classes', [] );
		$this->assertContains( 'tribe_events_page_tec-events-settings', $body_classes );

		$resources_desc = apply_filters( 'tec_help_hub_resources_description', '' );
		$this->assertStringContainsString( $this->mock_data->add_resources_description(''), $resources_desc );

		$support_title = apply_filters( 'tec_help_hub_support_title', '' );
		$this->assertStringContainsString( $this->mock_data->add_support_title(''), $support_title );
	}

	/**
	 * Test that initialization only happens once.
	 *
	 * @test
	 */
	public function initialization_happens_once(): void {
		// First initialization
		$this->mock_data->initialize();
		$initial_hooks = [
			'tec_help_hub_body_classes',
			'tec_help_hub_resources_description',
			'tec_help_hub_support_title',
		];

		// Count initial hooks
		$initial_hook_count = 0;
		foreach ( $initial_hooks as $hook ) {
			$initial_hook_count += has_filter( $hook );
		}

		// Try to initialize again
		$this->mock_data->initialize();

		// Count hooks after second initialization
		$final_hook_count = 0;
		foreach ( $initial_hooks as $hook ) {
			$final_hook_count += has_filter( $hook );
		}

		// Verify hook count hasn't changed
		$this->assertEquals( $initial_hook_count, $final_hook_count );
	}

	/**
	 * @test
	 */
	public function overall_template(): void {
		ob_start();
		$this->hub->render();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}

	/**
	 * @test
	 */
	public function section_rendering(): void {
		// Get the sections from the mock data
		$sections = $this->mock_data->create_resource_sections();

		// Test that sections are properly structured
		$this->assertIsArray( $sections );
		$this->assertNotEmpty( $sections );

		// Test that each section has required fields
		foreach ( $sections as $slug => $section ) {
			$this->assertArrayHasKey( 'title', $section );
			$this->assertArrayHasKey( 'slug', $section );
			$this->assertArrayHasKey( 'type', $section );
			$this->assertArrayHasKey( 'description', $section );

			// Validate content based on section type
			if ( $section['type'] === 'faqs' ) {
				$this->assertArrayHasKey( 'faqs', $section );
				$this->assertIsArray( $section['faqs'] );
				foreach ( $section['faqs'] as $faq ) {
					$this->assertArrayHasKey( 'question', $faq );
					$this->assertArrayHasKey( 'answer', $faq );
					$this->assertArrayHasKey( 'link_text', $faq );
					$this->assertArrayHasKey( 'link_url', $faq );
					$this->assertTrue( filter_var( $faq['link_url'], FILTER_VALIDATE_URL ) !== false, 'FAQ link URL must be valid' );
				}
			} else {
				// For non-FAQ sections, check for the items key that matches the section type
				$this->assertArrayHasKey( $section['type'], $section );
				$this->assertIsArray( $section[ $section['type'] ] );
				foreach ( $section[ $section['type'] ] as $item ) {
					$this->assertArrayHasKey( 'title', $item );
					$this->assertArrayHasKey( 'url', $item );
					// Icon is optional, only check if it exists
					if ( isset( $item['icon'] ) ) {
						$this->assertIsString( $item['icon'] );
					}
					$this->assertTrue( filter_var( $item['url'], FILTER_VALIDATE_URL ) !== false, 'Link URL must be valid' );
				}
			}
		}

		// Test that sections are properly rendered
		ob_start();
		$this->hub->render();
		$output = ob_get_clean();

		$this->assertMatchesHtmlSnapshot( $output );
	}
}
