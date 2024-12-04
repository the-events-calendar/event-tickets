<?php

use \Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_Uopz;
use Tribe__Tickets__Attendee_Registration__Template as Template;
use Tribe__Tickets__Attendee_Registration__Main as Main;

class Theme_Template_Test extends WPTestCase {
	use With_Uopz;

	/**
	 * Stores the current theme to reset after tests.
	 *
	 * @var string
	 */
	protected $original_theme;

	/**
	 * Store the current theme before each test.
	 *
	 * @before
	 */
	public function store_current_theme(): void {
		$this->original_theme = get_option( 'stylesheet' );
		tribe()->singleton( 'tickets.attendee_registration.template', new Template() );
		tribe()->singleton( 'tickets.attendee_registration', new Tribe__Tickets__Attendee_Registration__Main() );
	}

	/**
	 * Ensure the necessary themes are available.
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
		$this->set_class_fn_return( Tribe__Tickets__Attendee_Registration__Template::class, 'is_on_ar_page', $is_on_ar_page );
		// Overwrite the `is_on_custom_ar_page` method using uopz.
		$this->set_class_fn_return( Tribe__Tickets__Attendee_Registration__Template::class, 'is_on_custom_ar_page', $is_on_custom_ar_page );

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
		$template_instance = new Tribe__Tickets__Attendee_Registration__Template();

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
}
