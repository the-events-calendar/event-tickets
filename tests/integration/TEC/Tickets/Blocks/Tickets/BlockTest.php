<?php

namespace TEC\Tickets\Blocks\Tickets;

use Codeception\TestCase\WPTestCase;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;

class BlockTest extends WPTestCase {
	use SnapshotAssertions;

	public function test_get_registration_args(): void {
		$block = tribe( Block::class );

		$this->assertMatchesJsonSnapshot( json_encode( $block->get_registration_args( [] ), JSON_PRETTY_PRINT ) );
	}
}
