<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Quantity;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class RemoveTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/item/quantity/remove';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		/**
		 * @var \Tribe__Tickets__Commerce__PayPal__Main
		 */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$event = $this->get_mock_event( 'events/single/1.json' );
		$ids   = $this->create_many_paypal_tickets( 1, $event->ID );

		$ticket = $provider->get_ticket( $event->ID, $ids[0], [ 'price' => 99 ] );

		return [
			'ticket' => $ticket,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_remove_ticket_html() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets__tickets-item-quantity-remove', $html );
		$this->assertContains( $args['ticket']->name, $html );

		$this->assertMatchesSnapshot( $html );
	}
}