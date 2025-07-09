<?php

namespace TEC\Tickets\Commerce\Status;

use Codeception\TestCase\WPTestCase;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use TEC\Tickets\Commerce\Order;

class Status_Test extends WPTestCase {
	use Ticket_Maker;
	use Order_Maker;

	public function transitions_provider() {
		return [
			[ Action_Required::SLUG, Action_Required::SLUG, false ],
			[ Approved::SLUG, Approved::SLUG, false ],
			[ Completed::SLUG, Completed::SLUG, false ],
			[ Created::SLUG, Created::SLUG, false ],
			[ Denied::SLUG, Denied::SLUG, false ],
			[ Not_Completed::SLUG, Not_Completed::SLUG, false ],
			[ Pending::SLUG, Pending::SLUG, false ],
			[ Refunded::SLUG, Refunded::SLUG, true ], // Only status transition allowed from same to same to support multiple refunds. e.g. in stripe i can refund from X order total, Y at first and then Z where Z + Y <= X.
			[ Reversed::SLUG, Reversed::SLUG, false ],
			[ Trashed::SLUG, Trashed::SLUG, false ],
			[ Undefined::SLUG, Undefined::SLUG, false ],
			[ Unsupported::SLUG, Unsupported::SLUG, false ],
			[ Action_Required::SLUG, Approved::SLUG, true ],
			[ Action_Required::SLUG, Completed::SLUG, true ],
			[ Action_Required::SLUG, Created::SLUG, true ],
			[ Action_Required::SLUG, Denied::SLUG, true ],
			[ Action_Required::SLUG, Not_Completed::SLUG, true ],
			[ Action_Required::SLUG, Pending::SLUG, true ],
			[ Action_Required::SLUG, Refunded::SLUG, true ],
			[ Action_Required::SLUG, Reversed::SLUG, true ],
			[ Action_Required::SLUG, Trashed::SLUG, true ],
			[ Action_Required::SLUG, Undefined::SLUG, true ],
			[ Action_Required::SLUG, Unsupported::SLUG, true ],
			[ Approved::SLUG, Action_Required::SLUG, true ],
			[ Approved::SLUG, Completed::SLUG, true ],
			[ Approved::SLUG, Created::SLUG, true ],
			[ Approved::SLUG, Denied::SLUG, true ],
			[ Approved::SLUG, Not_Completed::SLUG, true ],
			[ Approved::SLUG, Pending::SLUG, true ],
			[ Approved::SLUG, Refunded::SLUG, true ],
			[ Approved::SLUG, Reversed::SLUG, true ],
			[ Approved::SLUG, Trashed::SLUG, true ],
			[ Approved::SLUG, Undefined::SLUG, true ],
			[ Approved::SLUG, Unsupported::SLUG, true ],
			[ Completed::SLUG, Action_Required::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Approved::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Created::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Denied::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Not_Completed::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Pending::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Refunded::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Reversed::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Trashed::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Undefined::SLUG, true ], // Completed is not final ?
			[ Completed::SLUG, Unsupported::SLUG, true ], // Completed is not final ?
			[ Created::SLUG, Action_Required::SLUG, true ],
			[ Created::SLUG, Approved::SLUG, true ],
			[ Created::SLUG, Completed::SLUG, true ],
			[ Created::SLUG, Denied::SLUG, true ],
			[ Created::SLUG, Not_Completed::SLUG, true ],
			[ Created::SLUG, Pending::SLUG, true ],
			[ Created::SLUG, Refunded::SLUG, true ],
			[ Created::SLUG, Reversed::SLUG, true ],
			[ Created::SLUG, Trashed::SLUG, true ],
			[ Created::SLUG, Undefined::SLUG, true ],
			[ Created::SLUG, Unsupported::SLUG, true ],
			[ Denied::SLUG, Action_Required::SLUG, false ], // Denied is final
			[ Denied::SLUG, Approved::SLUG, false ], // Denied is final
			[ Denied::SLUG, Completed::SLUG, false ], // Denied is final
			[ Denied::SLUG, Created::SLUG, false ], // Denied is final
			[ Denied::SLUG, Not_Completed::SLUG, false ], // Denied is final
			[ Denied::SLUG, Pending::SLUG, false ], // Denied is final
			[ Denied::SLUG, Refunded::SLUG, false ], // Denied is final
			[ Denied::SLUG, Reversed::SLUG, false ], // Denied is final
			[ Denied::SLUG, Trashed::SLUG, false ], // Denied is final
			[ Denied::SLUG, Undefined::SLUG, false ], // Denied is final
			[ Denied::SLUG, Unsupported::SLUG, false ], // Denied is final
			[ Not_Completed::SLUG, Action_Required::SLUG, true ],
			[ Not_Completed::SLUG, Approved::SLUG, true ],
			[ Not_Completed::SLUG, Completed::SLUG, true ],
			[ Not_Completed::SLUG, Created::SLUG, true ],
			[ Not_Completed::SLUG, Denied::SLUG, true ],
			[ Not_Completed::SLUG, Pending::SLUG, true ],
			[ Not_Completed::SLUG, Refunded::SLUG, true ],
			[ Not_Completed::SLUG, Reversed::SLUG, true ],
			[ Not_Completed::SLUG, Trashed::SLUG, true ],
			[ Not_Completed::SLUG, Undefined::SLUG, true ],
			[ Not_Completed::SLUG, Unsupported::SLUG, true ],
			[ Pending::SLUG, Action_Required::SLUG, true ],
			[ Pending::SLUG, Approved::SLUG, true ],
			[ Pending::SLUG, Completed::SLUG, true ],
			[ Pending::SLUG, Created::SLUG, true ],
			[ Pending::SLUG, Denied::SLUG, true ],
			[ Pending::SLUG, Not_Completed::SLUG, true ],
			[ Pending::SLUG, Refunded::SLUG, true ],
			[ Pending::SLUG, Reversed::SLUG, true ],
			[ Pending::SLUG, Trashed::SLUG, true ],
			[ Pending::SLUG, Undefined::SLUG, true ],
			[ Pending::SLUG, Unsupported::SLUG, true ],
			[ Refunded::SLUG, Action_Required::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Approved::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Completed::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Created::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Denied::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Not_Completed::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Pending::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Reversed::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Trashed::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Undefined::SLUG, false ], // Refunded is final.
			[ Refunded::SLUG, Unsupported::SLUG, false ], // Refunded is final.
			[ Reversed::SLUG, Action_Required::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Approved::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Completed::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Created::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Denied::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Not_Completed::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Pending::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Refunded::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Trashed::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Undefined::SLUG, false ], // Reversed is final.
			[ Reversed::SLUG, Unsupported::SLUG, false ], // Reversed is final.
			[ Trashed::SLUG, Action_Required::SLUG, true ],
			[ Trashed::SLUG, Approved::SLUG, true ],
			[ Trashed::SLUG, Completed::SLUG, true ],
			[ Trashed::SLUG, Created::SLUG, true ],
			[ Trashed::SLUG, Denied::SLUG, true ],
			[ Trashed::SLUG, Not_Completed::SLUG, true ],
			[ Trashed::SLUG, Pending::SLUG, true ],
			[ Trashed::SLUG, Refunded::SLUG, true ],
			[ Trashed::SLUG, Reversed::SLUG, true ],
			[ Trashed::SLUG, Undefined::SLUG, true ],
			[ Trashed::SLUG, Unsupported::SLUG, true ],
			[ Undefined::SLUG, Action_Required::SLUG, true ],
			[ Undefined::SLUG, Approved::SLUG, true ],
			[ Undefined::SLUG, Completed::SLUG, true ],
			[ Undefined::SLUG, Created::SLUG, true ],
			[ Undefined::SLUG, Denied::SLUG, true ],
			[ Undefined::SLUG, Not_Completed::SLUG, true ],
			[ Undefined::SLUG, Pending::SLUG, true ],
			[ Undefined::SLUG, Refunded::SLUG, true ],
			[ Undefined::SLUG, Reversed::SLUG, true ],
			[ Undefined::SLUG, Trashed::SLUG, true ],
			[ Undefined::SLUG, Unsupported::SLUG, true ],
			[ Unsupported::SLUG, Action_Required::SLUG, true ],
			[ Unsupported::SLUG, Approved::SLUG, true ],
			[ Unsupported::SLUG, Completed::SLUG, true ],
			[ Unsupported::SLUG, Created::SLUG, true ],
			[ Unsupported::SLUG, Denied::SLUG, true ],
			[ Unsupported::SLUG, Not_Completed::SLUG, true ],
			[ Unsupported::SLUG, Pending::SLUG, true ],
			[ Unsupported::SLUG, Refunded::SLUG, true ],
			[ Unsupported::SLUG, Reversed::SLUG, true ],
			[ Unsupported::SLUG, Trashed::SLUG, true ],
			[ Unsupported::SLUG, Undefined::SLUG, true ],
		];
	}

	/**
	 * @dataProvider transitions_provider
	 */
	public function test_status_can_change_to( $from, $to, $result ) {
		$from = tribe( Status_Handler::class )->get_by_slug( $from );
		$to   = tribe( Status_Handler::class )->get_by_slug( $to );

		$this->assertSame( $result, $from->can_change_to( $to ), "Unexpected result for change from {$from->get_slug()} to {$to->get_slug()}" );
	}

	/**
	 * @dataProvider transitions_provider
	 */
	public function test_order_can_change_to( $from, $to, $result ) {
		$post = self::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$ticket_id_1 = $this->create_tc_ticket( $post, 10 );
		$ticket_id_2 = $this->create_tc_ticket( $post, 20 );

		$order = $this->create_order( [ $ticket_id_1 => 1, $ticket_id_2 => 2 ], [ 'order_status' => $from ] );
		// tribe( Order::class )->unlock_order( $order->ID );
		$from = tribe( Status_Handler::class )->get_by_slug( $from );
		$to   = tribe( Status_Handler::class )->get_by_slug( $to );

		$this->assertSame( $result, tribe( Order::class )->can_transition_to( $to, $order->ID ), "Unexpected result for order transition from {$from->get_slug()} to {$to->get_slug()}" );
	}
}
