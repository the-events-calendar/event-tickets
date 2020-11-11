<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class ExtraTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/extra';

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
		$ids   = $this->create_many_paypal_tickets( 1, $event->ID, [ 'price' => 99 ] );

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $event->ID, $ids[0] );

		return [
			'post_id'             => $event->ID,
			'ticket'              => $ticket,
			'provider'            => $provider,
			'provider_id'         => $provider->class_name,
			'has_tickets_on_sale' => true,
			'is_sale_past'        => false,
			'is_sale_future'      => true,
			'currency'            => tribe( 'tickets.commerce.currency' ),
			'is_mini'             => false,
			'is_modal'            => false,
			'submit_button_name'  => 'cart-button',
			'cart_url'            => 'http://wordpress.test/cart/?foo',
			'checkout_url'        => 'http://wordpress.test/checkout/?bar',
			'threshold'           => 0,
			'key'                 => 0,
			'available_count'     => $ticket->available(),
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_with_price_suffix() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$args['ticket']->price_suffix = 'simple_prefix';

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets__tickets-item-extra--price-suffix', $html );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( [
				$args['post_id'],
				$args['ticket']->ID,
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_without_price_suffix() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$args['ticket']->price_suffix = '';

		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( [
				$args['post_id'],
				$args['ticket']->ID,
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
