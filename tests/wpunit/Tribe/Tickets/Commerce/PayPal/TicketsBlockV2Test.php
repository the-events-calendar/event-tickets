<?php

namespace Tribe\Tickets\Commerce\PayPal;

include_once __DIR__ . '/TicketsBlockTest.php';

/**
 * @group block
 * @group block-paypal
 * @group editor
 * @group editor-paypal
 * @group capacity
 * @group capacity-paypal
 * @group v2
 */
class TicketsBlockV2Test extends TicketsBlockTest {

	/**
	 * Handle set up.
	 */
	public function setUp() {
		$this->use_v2 = true;

		parent::setUp();
	}
}
