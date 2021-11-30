<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart\Item;

use TEC\Tickets\Commerce\Ticket;
use Tribe\Tickets\Test\Testcases\TicketsCommerceSnapshotTestCase;

class ItemDetailsTest extends TicketsCommerceSnapshotTestCase {

	protected $partial_path = 'checkout/cart/item/details';

	public function test_should_render_cart_item_details() {

		$ticket_id        = $this->get_mock_thing( 'tickets/1.json' );
		$ticket['obj'] = tribe( Ticket::class )->get_ticket( $ticket_id );
		$ticket['ticket_id'] = $ticket_id;

		$this->assertMatchesHtmlSnapshot( $this->get_partial_html( [
				'item' => $ticket,
			]
		) );

	}
}
