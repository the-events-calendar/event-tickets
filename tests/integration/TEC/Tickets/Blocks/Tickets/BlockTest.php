<?php

namespace TEC\Tickets\Blocks\Tickets;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\Assets\Assets;

class BlockTest extends WPTestCase {
	use SnapshotAssertions;

	/**
	 * @after
	 */
	public function reset_screen(): void {
		unset( $GLOBALS['current_screen'] );
	}

	public function test_get_registration_args(): void {
		$block = tribe( Block::class );

		$this->assertMatchesJsonSnapshot( json_encode( $block->get_registration_args( [] ), JSON_PRETTY_PRINT ) );
	}

	public function test_assets_source(): void {
		tribe( Block::class )->register_editor_scripts();

		$this->assertEquals( plugins_url( '/build/Tickets/Blocks/Tickets/editor.js', EVENT_TICKETS_MAIN_PLUGIN_FILE ), Assets::init()->get( Block::EDITOR_SCRIPT_SLUG )->get_url( false ) );
		$this->assertEquals( plugins_url( '/build/Tickets/Blocks/Tickets/style-editor.css', EVENT_TICKETS_MAIN_PLUGIN_FILE ), Assets::init()->get( Block::EDITOR_STYLE_SLUG )->get_url( false ) );
	}

	/**
	 * @test
	 */
	public function it_should_put_the_editor_style_in_the_editor_canvas_once_the_block_registers(): void {
		tribe( 'tickets.editor.assets' );
		Assets::init()->remove( Block::EDITOR_STYLE_SLUG );
		wp_deregister_style( Block::EDITOR_STYLE_SLUG );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'post' );
		get_current_screen()->is_block_editor( true );

		tribe( Block::class )->register();
		$iframe_styles = _wp_get_iframed_editor_assets()['styles'];

		$this->assertTrue( wp_style_is( Block::EDITOR_STYLE_SLUG, 'registered' ) );
		$this->assertStringContainsString( "id='" . Block::EDITOR_STYLE_SLUG . "-css'", $iframe_styles );
	}

	/**
	 * @test
	 */
	public function it_should_print_the_editor_script_after_the_editor_packages_it_reads(): void {
		tribe( 'tickets.editor.assets' );
		Assets::init()->remove( Block::EDITOR_SCRIPT_SLUG );
		wp_deregister_script( Block::EDITOR_SCRIPT_SLUG );
		unregister_block_type( 'tribe/tickets' );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		set_current_screen( 'page' );
		get_current_screen()->is_block_editor( true );

		tribe( Block::class )->register();

		/*
		 * Same order as `wp-admin/edit-form-blocks.php`: the editor assets action fires before
		 * `admin-header.php` fires `admin_enqueue_scripts`.
		 */
		do_action( 'enqueue_block_editor_assets' );
		do_action( 'admin_enqueue_scripts', 'post.php' );

		$scripts = wp_scripts();
		$scripts->all_deps( $scripts->queue );
		$print_order = $scripts->to_do;

		$editor_script = array_search( Block::EDITOR_SCRIPT_SLUG, $print_order, true );
		$this->assertNotFalse( $editor_script );

		/*
		 * The script reads the `window.tribe.tickets.data` and `window.tribe.common` globals these
		 * handles define, without declaring them as dependencies.
		 */
		foreach ( [ 'tribe-common-gutenberg-modules', 'tribe-common-gutenberg-main', 'tribe-tickets-gutenberg-main' ] as $package ) {
			$package_position = array_search( $package, $print_order, true );
			$this->assertNotFalse( $package_position, "{$package} should be printed in the editor." );
			$this->assertGreaterThan( $package_position, $editor_script, "The editor script should print after {$package}." );
		}
	}
}
