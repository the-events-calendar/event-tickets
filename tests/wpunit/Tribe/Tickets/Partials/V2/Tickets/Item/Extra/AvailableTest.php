<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Extra;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class AvailableTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/item/extra/available';

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

		$ticket = $provider->get_ticket( $event->ID, $ids[0] );

		// default Ticket scenario.
		$ticket->capacity = 20;

		return [
			'ticket'    => $ticket,
			'is_mini'   => false,
			'threshold' => 0,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_available_block() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_available_block_if_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$args['is_mini'] = true;
		$html            = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}