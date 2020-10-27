<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Content;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class DescriptionTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/item/content/description';

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
			'ticket'   => $ticket,
			'is_modal' => true,
			'is_mini'  => false,
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
	public function test_should_not_render_if_ticket_show_description_is_false() {
		$template = tribe( 'tickets.editor.template' );

		add_filter( 'tribe_tickets_show_description', '__return_false' );

		$html = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_ticket_description_is_empty() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$args['ticket']->description = '';

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_not_is_mini_and_valid_ticket() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [
				$args['ticket']->ID,
			]
		);

		// Make sure we have the Modal class added.
		$this->assertContains( 'tribe__details__content__modal', $html );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_only_description_if_is_modal_false() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_modal' => false,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [
				$args['ticket']->ID,
			]
		);

		$driver->setTolerableDifferencesPrefixes( [
			'Test ticket description for ',
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
