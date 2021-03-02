<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Extra\Available;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class UnlimitedTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/extra/available/unlimited';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args( $capacity = null ) {
		/**
		 * @var \Tribe__Tickets__Commerce__PayPal__Main
		 */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$event = $this->get_mock_event( 'events/single/1.json' );
		$ids   = $this->create_many_paypal_tickets( 1, $event->ID, [
			'tribe-ticket' => [
				'capacity' => null !== $capacity ? $capacity : -1,
			],
		] );

		$ticket = $provider->get_ticket( $event->ID, $ids[0] );

		return [
			'ticket' => $ticket,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_unlimited_text() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_unlimited'   => true,
			'show_unlimited' => true,
		];

		$args = array_merge( $this->get_default_args(), $args );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_show_unlimited_is_false() {
		add_filter( 'tribe_tickets_block_show_unlimited_availability', '__return_false' );

		$template = tribe( 'tickets.editor.template' );

		$args = array_merge( $this->get_default_args(), [ 'show_unlimited' => false ] );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_available_quantity_is_not_unlimited() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args( 25 );

		$args['is_unlimited'] = false;

		$html = $template->template( $this->partial_path, $args, false );
		$this->assertMatchesSnapshot( $html );
	}
}
