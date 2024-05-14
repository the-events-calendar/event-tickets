<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use Tribe\Tests\Traits\With_Uopz;

class Assets_Test extends Controller_Test_Case {
	use With_Uopz;
	use SnapshotAssertions;

	protected string $controller_class = Assets::class;

	public function test_get_utils_data(): void {
		$this->set_fn_return( 'wp_create_nonce', '8298ff6616' );
		$controller = $this->make_controller();
		$this->assertMatchesJsonSnapshot( wp_json_encode( $controller->get_utils_data(), JSON_SNAPSHOT_OPTIONS ) );
	}
}