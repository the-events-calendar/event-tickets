<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Partials\V2TestCase;

class InactiveTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/inactive';

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

		return [
			'has_tickets_on_sale' => false,
			'is_sale_past'        => false,
			'provider'            => $provider
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_if_has_tickets_on_sale_is_false() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$event    = $this->get_mock_event( 'events/single/1.json' );
		$ids      = $this->create_many_paypal_tickets( 2, $event->ID, [ 'price' => 99 ] );


		foreach ( $ids as $ticket ) {
			$args['tickets'][] = $args['provider']->get_ticket( $event->ID, $ticket );
		}

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	public function test_should_render_past_message_if_is_sale_past_true(){
		$template = tribe( 'tickets.editor.template' );

		$args                 = $this->get_default_args();
		$args['is_sale_past'] = true;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_has_tickets_on_sale_is_true() {
		$template = tribe( 'tickets.editor.template' );

		$args                        = $this->get_default_args();
		$args['has_tickets_on_sale'] = true;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}
