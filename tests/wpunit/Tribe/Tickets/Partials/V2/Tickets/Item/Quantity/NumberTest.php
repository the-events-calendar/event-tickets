<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Quantity;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class NumberTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/item/quantity/number';

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
			'must_login' => false,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_number_html() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets__tickets-item-quantity-number-input', $html );

		$driver = $this->get_html_output_driver();

		$driver->setTimeDependentAttributes(
			[
				'value',
				'max',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_disabled_number_when_must_login_is_true() {
		$template = tribe( 'tickets.editor.template' );

		$args               = $this->get_default_args();
		$args['must_login'] = true;
		$html               = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets__tickets-item-quantity-number-input', $html );
		$this->assertContains( 'tribe-tickets__disabled', $html );

		$driver = $this->get_html_output_driver();

		$driver->setTimeDependentAttributes(
			[
				'value',
				'max',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}
}