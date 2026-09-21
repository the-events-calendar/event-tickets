<?php

namespace TEC\Tickets\Commerce\Admin_Tables;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Order;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use WP_Screen;

class OrdersTest extends WPTestCase {
	use Order_Maker;
	use Ticket_Maker;
	use With_Tickets_Commerce;

	/**
	 * @before
	 */
	public function set_up_test_case() {
		global $current_screen, $typenow;
		$current_screen = WP_Screen::get( 'edit-' . Order::POSTTYPE );
		$typenow        = Order::POSTTYPE;
	}

	/**
	 * @test
	 */
	public function it_should_render_the_ticket_name_in_the_purchased_column() {
		/* Two of the ticket, so the line item quantity is distinguishable from the default of one. */
		$quantity  = 2;
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $event_id );
		$order     = $this->create_order( [ $ticket_id => $quantity ] );

		$name = get_post( $ticket_id )->post_title;

		$this->assertSame(
			"<div class='tribe-line-item'>{$quantity} - {$name}</div>",
			( new Orders() )->column_purchased( tec_tc_get_order( $order->ID ) )
		);
	}

	/**
	 * @test
	 */
	public function it_should_fall_back_to_a_generic_label_when_the_ticket_is_gone() {
		/* Two of the ticket, so the fallback label has to resolve to its plural form. */
		$quantity  = 2;
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $event_id );
		$order     = $this->create_order( [ $ticket_id => $quantity ] );

		wp_delete_post( $ticket_id, true );

		$name = _n( 'Ticket', 'Tickets', $quantity, 'event-tickets' );

		$this->assertSame(
			"<div class='tribe-line-item'>{$quantity} - {$name}</div>",
			( new Orders() )->column_purchased( tec_tc_get_order( $order->ID, OBJECT, 'raw', true ) )
		);
	}
}
