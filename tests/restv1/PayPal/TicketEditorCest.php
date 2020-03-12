<?php

namespace Tribe\Tickets\Test\REST\V1\PayPal;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as Ticket_Maker;
use Tribe\Tickets\Test\Testcases\REST\V1\BaseTicketEditorCest;

/**
 * @group block
 * @group block-paypal
 * @group editor
 * @group editor-paypal
 * @group capacity
 * @group capacity-paypal
 */
class TicketEditorCest extends BaseTicketEditorCest {

	use Ticket_Maker;

	/**
	 * Get list of providers for test.
	 *
	 * @return array List of providers.
	 */
	protected function get_providers() {
		return [
			'Tribe__Tickets__Commerce__PayPal__Main' => 'tribe-commerce',
		];
	}
}
