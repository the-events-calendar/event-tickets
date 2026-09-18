<?php

namespace TEC\Tickets\Blocks\Ticket;

use Codeception\TestCase\WPTestCase;
use Tribe\Tests\Traits\With_WP_Version_Tolerant_Snapshots;
use TEC\Tickets\Blocks\Tickets\Block;

class BlockTest extends WPTestCase {
	use With_WP_Version_Tolerant_Snapshots;

	public function test_get_registration_args(): void {
		$block = tribe( Block::class );

		$this->assertMatchesJsonSnapshot( json_encode( $block->get_registration_args( [] ), JSON_PRETTY_PRINT ) );
	}
}
