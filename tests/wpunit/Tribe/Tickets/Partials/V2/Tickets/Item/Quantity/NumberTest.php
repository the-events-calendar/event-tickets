<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Quantity;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe__Tickets__Tickets_Handler;

class NumberTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/quantity/number';

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		/** @var \Tribe__Tickets__Commerce__PayPal__Main */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$event = $this->get_mock_event( 'events/single/1.json' );
		$ids   = $this->create_many_paypal_tickets( 1, $event->ID );

		$ticket = $provider->get_ticket( $event->ID, $ids[0], [ 'price' => 99 ] );

		return [
			'handler'    => $tickets_handler,
			'ticket'     => $ticket,
			'must_login' => false,
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_number_html() {
		$args = $this->get_default_args();
		$html = $this->template_html( $args );

		$this->assertContains( 'tribe-tickets__tickets-item-quantity-number-input', $html );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [ $args['ticket']->ID ] );

		$driver->setTimeDependentAttributes(
			[
				'value',
				'max',
			]
		);

		$driver->setTolerableDifferencesPrefixes(
			[
				'tribe-tickets__tickets-item-quantity-number--',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_disabled_number_when_must_login_is_true() {
		$override = [
			'must_login' => true,
		];

		$args = $this->get_args( $override );

		$html = $this->template_html( $args );

		$this->assertContains( 'tribe-tickets__tickets-item-quantity-number-input', $html );
		$this->assertContains( 'tribe-tickets__disabled', $html );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [ $args['ticket']->ID ] );

		$driver->setTimeDependentAttributes(
			[
				'value',
				'max',
			]
		);

		$driver->setTolerableDifferencesPrefixes(
			[
				'tribe-tickets__tickets-item-quantity-number--',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
