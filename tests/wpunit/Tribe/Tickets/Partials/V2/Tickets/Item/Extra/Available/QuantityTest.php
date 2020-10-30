<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Extra\Available;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class QuantityTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/item/extra/available/quantity';

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

		$ids = $this->create_many_paypal_tickets( 1, $event->ID );

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $event->ID, $ids[0] );

		// default Ticket scenario.
		$ticket->capacity = 20;

		return [
			'ticket'          => $ticket,
			'threshold'       => 0,
			'available_count' => $ticket->available(),
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_quantity_block() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_quantity_block_if_threshold_smaller_than_available_quantity() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		// Threshold is less than the set capacity of 20.
		$args['threshold'] = 10;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}

	/**
	 * @test
	 */
	public function test_should_render_quantity_block_if_threshold_greater_than_available_quantity() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		// Threshold is greater than the set capacity of 20.
		$args['threshold'] = 25;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}

}
