<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Opt_Out;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class HiddenTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/opt-out/hidden';

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
			'ticket'   => $ticket,
			'is_modal' => true,
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_modal() {
		$template = tribe( 'tickets.editor.template' );

		$args             = $this->get_default_args();
		$args['is_modal'] = false;
		$html             = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_hidden_opt_out_html() {
		$template = tribe( 'tickets.editor.template' );
		add_filter( 'tribe_tickets_plus_hide_attendees_list_optout', '__return_false', 99 );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences(
			[ $args['ticket']->ID ]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_attendee_optout() {
		$template = tribe( 'tickets.editor.template' );

		add_filter( 'tribe_tickets_plus_hide_attendees_list_optout', '__return_true', 99 );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}
}