<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class Opt_OutTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/item/opt-out';

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

		return [
			'post_id'  => $event->ID,
			'ticket'   => $ticket,
			'is_mini'  => false,
			'is_modal' => false,
			'handler'  => tribe( 'tickets.handler' ),
			'privacy'  => tribe( 'tickets.privacy' ),
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_is_modal() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_modal' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_hide_attendee_list_optout() {
		$template = tribe( 'tickets.editor.template' );

		$html = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_without_attendee_list_optout() {
		$template = tribe( 'tickets.editor.template' );

		add_filter( 'tribe_tickets_plus_hide_attendees_list_optout', '__return_false', 99 );

		$args = $this->get_default_args();
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