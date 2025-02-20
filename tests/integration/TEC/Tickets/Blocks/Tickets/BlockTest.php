<?php

namespace TEC\Tickets\Blocks\Tickets;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\Assets\Assets;

class BlockTest extends WPTestCase {
	use SnapshotAssertions;

	public function test_get_registration_args(): void {
		$block = tribe( Block::class );

		$this->assertMatchesJsonSnapshot( json_encode( $block->get_registration_args( [] ), JSON_PRETTY_PRINT ) );
	}

	public function test_assets_source(): void {
		tribe( Block::class )->register_editor_scripts();

		$this->assertEquals( plugins_url( '/build/Tickets/Blocks/Tickets/editor.js', EVENT_TICKETS_MAIN_PLUGIN_FILE ), Assets::init()->get( Block::EDITOR_SCRIPT_SLUG )->get_url( false ) );
		$this->assertEquals( plugins_url( '/build/Tickets/Blocks/Tickets/editor.css', EVENT_TICKETS_MAIN_PLUGIN_FILE ), Assets::init()->get( Block::EDITOR_STYLE_SLUG )->get_url( false ) );
	}
}
