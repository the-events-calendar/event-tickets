<?php

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Attendee_Registration__Template as Template;
use Tribe__Tickets__Attendee_Registration__Main as Attendee_Registration_Main;

class Theme_Template_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * Stores the current theme to reset after tests.
	 *
	 * @var string
	 */
	protected $original_theme;

	/**
	 * Reusable Post
	 *
	 * @var WP_Post
	 */
	protected $reusable_post;

	/**
	 * Store the current theme before each test.
	 *
	 * @before
	 */
	public function store_current_theme(): void {
		$this->original_theme = get_option( 'stylesheet' );
		tribe()->singleton( 'tickets.attendee_registration.template', new Template() );
		tribe()->singleton( 'tickets.attendee_registration', new Attendee_Registration_Main() );
	}

	/**
	 * Setup the Post
	 *
	 * @before
	 */
	public function setup_reusable_post(): void {
		$post_id             = wp_insert_post(
			[
				'post_title'   => 'Test Post',
				'post_content' => 'Content of the test post.',
				'post_status'  => 'publish',
				'post_type'    => 'post',
			]
		);
		$this->reusable_post = get_post( $post_id );
	}

	/**
	 * Clean up reusable Post
	 *
	 * @after
	 */
	public function teardown_reusable_post(): void {
		wp_delete_post( $this->reusable_post->ID, true );
	}

	/**
	 * Ensure the necessary themes are available.
	 *
	 * @test
	 */
	public function ensure_themes_exist(): void {
		$this->assertTrue(
			wp_get_theme( 'twentytwentyfour' )->exists(),
			'Theme "twentytwentyfour" must be installed for this test.'
		);

		$this->assertTrue(
			wp_get_theme( 'twentytwenty' )->exists(),
			'Theme "twentytwenty" must be installed for this test.'
		);
	}

	/**
	 * Restore the original theme after each test.
	 *
	 * @after
	 */
	public function restore_original_theme(): void {
		if ( $this->original_theme && $this->original_theme !== get_option( 'stylesheet' ) ) {
			switch_theme( $this->original_theme );
		}
	}

	/**
	 * Test switching themes and confirming theme properties.
	 *
	 * @test
	 * @dataProvider theme_validation_provider
	 */
	public function should_properly_set_page_template( Closure $fixture ): void {
		[ $theme, $is_block_theme, $singular_template, $is_on_ar_page, $is_on_custom_ar_page, $expected_template ] = $fixture();

		// Overwrite the `is_on_ar_page` method using uopz.
		$this->set_class_fn_return( Template::class, 'is_on_ar_page', $is_on_ar_page );
		// Overwrite the `is_on_custom_ar_page` method using uopz.
		$this->set_class_fn_return( Template::class, 'is_on_custom_ar_page', $is_on_custom_ar_page );

		// Assert that the active theme is correct.
		$this->assertEquals(
			$theme,
			wp_get_theme()->get( 'Name' ),
			sprintf( 'Expected theme to be "%s", but got "%s".', $theme, wp_get_theme()->get( 'Name' ) )
		);

		// Assert if the theme is a block theme.
		$this->assertEquals(
			$is_block_theme,
			wp_is_block_theme(),
			sprintf( 'Theme "%s" block status expected to be "%s".', $theme, var_export( $is_block_theme, true ) )
		);

		// Mock the Tribe__Tickets__Attendee_Registration__Template instance.
		$template_instance = new Template();

		$this->assertEquals( $is_on_ar_page, $template_instance->is_on_ar_page(), 'is_on_ar_page should match' );

		$this->assertEquals( $is_on_custom_ar_page, $template_instance->is_on_custom_ar_page(), '$is_on_custom_ar_page should match' );

		// Apply the mocked method and get the page template.
		$page_template = $template_instance->set_page_template( $singular_template );

		$this->assertEquals( $expected_template, $page_template, 'Template should match' );
	}

	/**
	 * Data provider for theme validation.
	 *
	 * @return Generator
	 */
	public function theme_validation_provider(): Generator {
		yield 'twentytwenty non-block theme, not on AR page' => [
			function () {
				switch_theme( 'twentytwenty' );

				return [
					'Twenty Twenty', // Theme name.
					false, // Block theme status.
					locate_template( 'singular.php' ), // Singular theme file.
					false, // is_on_ar_page.
					false, // is_on_custom_ar_page (irrelevant here).
					locate_template( 'singular.php' ), // Expected template.
				];
			},
		];

		yield 'twentytwenty non-block theme, on AR page, not on custom AR page' => [
			function () {
				switch_theme( 'twentytwenty' );

				return [
					'Twenty Twenty', // Theme name.
					false, // Block theme status.
					locate_template( 'singular.php' ), // Singular theme file.
					true, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					locate_template( 'templates/template-cover.php' ), // Expected template.
				];
			},
		];

		yield 'twentytwenty missing singular.php' => [
			function () {
				switch_theme( 'twentytwenty' );

				return [
					'Twenty Twenty', // Theme name.
					false, // Block theme status.
					'', // Singular theme file (mock as missing).
					true, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					locate_template( 'templates/template-cover.php' ), // Expected fallback template.
				];
			},
		];

		yield 'twentytwenty non-block theme, on AR page, on custom AR page' => [
			function () {
				switch_theme( 'twentytwenty' );

				return [
					'Twenty Twenty', // Theme name.
					false, // Block theme status.
					locate_template( 'singular.php' ), // Singular theme file.
					true, // is_on_ar_page.
					true, // is_on_custom_ar_page.
					get_page_template(), // Expected template.
				];
			},
		];

		yield 'twentytwentyfour block theme, not on AR page' => [
			function () {
				switch_theme( 'twentytwentyfour' );

				return [
					'Twenty Twenty-Four', // Theme name.
					true, // Block theme status.
					'', // Singular theme file.
					false, // is_on_ar_page.
					false, // is_on_custom_ar_page (irrelevant here).
					'', // Expected template.
				];
			},
		];

		yield 'twentytwentyfour block theme, on AR page, not on custom AR page' => [
			function () {
				switch_theme( 'twentytwentyfour' );

				$template_slug = 'page'; // Default to 'page'.
				$template      = get_template_directory() . DIRECTORY_SEPARATOR . $template_slug . '.html';

				return [
					'Twenty Twenty-Four', // Theme name.
					true, // Block theme status.
					'', // Singular theme file.
					true, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					locate_block_template( $template, 'page', [ $template_slug, 'page' ] ), // Expected template.
				];
			},
		];

		yield 'twentytwentyfour block theme, on AR page, on custom AR page' => [
			function () {
				switch_theme( 'twentytwentyfour' );

				return [
					'Twenty Twenty-Four', // Theme name.
					true, // Block theme status.
					'', // Singular theme file.
					true, // is_on_ar_page.
					true, // is_on_custom_ar_page.
					get_page_template(), // Expected template.
				];
			},
		];
	}

	/**
	 * Test `setup_context` behavior with various scenarios.
	 *
	 * @test
	 * @dataProvider setup_context_provider
	 */
	public function should_setup_context_correctly( Closure $fixture ): void {
		[ $posts, $query, $is_main_query, $is_on_ar_page, $is_on_custom_ar_page, $expected ] = $fixture();

		// Overwrite the `is_main_query` method using uopz.
		$this->set_class_fn_return( WP_Query::class, 'is_main_query', $is_main_query );

		// Mock `is_on_ar_page` and `is_on_custom_ar_page`.
		$this->set_class_fn_return( Template::class, 'is_on_ar_page', $is_on_ar_page );
		$this->set_class_fn_return( Template::class, 'is_on_custom_ar_page', $is_on_custom_ar_page );

		// Create an instance of the class to test.
		$template_instance = new Template();

		// Run the method under test.
		$result = $template_instance->setup_context( $posts, $query );

		// Assert the result matches the expectation.
		$this->assertEquals( $expected, $result, 'setup_context result should match the expected output.' );
	}

	/**
	 * Data provider for `setup_context`.
	 *
	 * @return Generator
	 */
	public function setup_context_provider(): Generator {
		yield 'Not the main query' => [
			function () {
				return [
					[ $this->reusable_post ], // Posts.
					new WP_Query(), // Query object.
					false, // is_main_query.
					false, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					[ $this->reusable_post ], // Expected result (unchanged).
				];
			},
		];

		yield 'Main query, not on AR page' => [
			function () {
				return [
					[ $this->reusable_post ], // Posts.
					new WP_Query(), // Query object.
					true, // is_main_query.
					false, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					[ $this->reusable_post ], // Expected result (unchanged).
				];
			},
		];

		yield 'On AR page, on custom AR page' => [
			function () {
				return [
					[ $this->reusable_post ], // Posts.
					new WP_Query(), // Query object.
					true, // is_main_query.
					true, // is_on_ar_page.
					true, // is_on_custom_ar_page.
					[ $this->reusable_post ], // Expected result (unchanged).
				];
			},
		];

		yield 'On AR page, not on custom AR page' => [
			function () {
				$template = new Tribe__Tickets__Attendee_Registration__Template();
				// Grab our Spoofed Page.
				$spoofed_page = $template->spoofed_page();

				return [
					[ $this->reusable_post ], // Posts.
					new WP_Query(), // Query object.
					true, // is_main_query.
					true, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					[ $spoofed_page ], // Expected spoofed post.
				];
			},
		];

		yield 'Not main query, on AR page, not on custom AR page' => [
			function () {
				// Create a reusable post (already handled by your setup_reusable_post).
				$template = new Tribe__Tickets__Attendee_Registration__Template();

				return [
					[ $this->reusable_post ], // Posts.
					new WP_Query(), // Query object.
					false, // is_main_query (not the main query).
					true, // is_on_ar_page.
					false, // is_on_custom_ar_page.
					[ $this->reusable_post ], // Expected result remains unchanged.
				];
			},
		];
	}

}
