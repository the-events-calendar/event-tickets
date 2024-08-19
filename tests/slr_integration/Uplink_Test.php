<?php

namespace TEC\Tickets\Seating;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;

class Uplink_Test extends Controller_Test_Case {
	use SnapshotAssertions;

	protected $controller_class = Uplink::class;

	public function test_get_authorize_button_text_not_authenticated(): void {
		// Before registering the controller.
		$this->assertEquals(
			'Connect',
			apply_filters(
				'stellarwp/uplink/tec/tec-seating/view/authorize_button/link_text',
				'Connect',
				false
			)
		);

		$controller = $this->make_controller();
		$controller->register();

		// After registering the controller.
		$this->assertMatchesStringSnapshot(
			apply_filters(
				'stellarwp/uplink/tec/tec-seating/view/authorize_button/link_text',
				'Connect',
				false
			)
		);
	}

	public function test_get_authorize_button_text_authenticated(): void {
		// Before registering the controller.
		$this->assertEquals(
			'Disconnect',
			apply_filters(
				'stellarwp/uplink/tec/tec-seating/view/authorize_button/link_text',
				'Disconnect',
				true
			)
		);

		$controller = $this->make_controller();
		$controller->register();

		// After registering the controller.
		$this->assertMatchesStringSnapshot(
			apply_filters(
				'stellarwp/uplink/tec/tec-seating/view/authorize_button/link_text',
				'Disconnect',
				true
			)
		);
	}
}