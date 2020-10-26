<?php

namespace Tribe\Tickets\Commerce\RSVP;

include_once __DIR__ . '/RSVPBlockTest.php';

/**
 * @group block
 * @group block-rsvp
 * @group editor
 * @group editor-rsvp
 * @group capacity
 * @group capacity-rsvp
 * @group v2
 */
class RSVPBlockV2Test extends RSVPBlockTest {

	/**
	 * Handle set up.
	 */
	public function setUp() {
		$this->use_v2 = true;

		parent::setUp();
	}
}
