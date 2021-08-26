<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item\Content;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class TitleTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/content/title';

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

		$event_id = $this->factory()->event->create(
			[
				'post_title' => 'TEC event for ticket item',
				'post_name'  => 'tec-event-for-ticket',
			]
		);

		$ids     = $this->create_many_paypal_tickets( 1, $event_id );

		$ticket = $provider->get_ticket( $event_id, $ids[0] );

		return [
			'post_id'                     => $event_id,
			'ticket'                      => $ticket,
			'provider'                    => $provider,
			'provider_id'                 => $provider->class_name,
			'has_tickets_on_sale'         => true,
			'is_sale_past'                => false,
			'is_sale_future'              => true,
			'currency'                    => tribe( 'tickets.commerce.currency' ),
			'is_mini'                     => false,
			'is_modal'                    => false,
			'submit_button_name'          => 'cart-button',
			'cart_url'                    => 'http://wordpress.test/cart/?foo',
			'checkout_url'                => 'http://wordpress.test/checkout/?bar',
			'threshold'                   => 0,
			'key'                         => 0,
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_show_description_if_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets--no-description', $html );
		$this->assertContains( 'tribe-tickets__tickets-item-content-subtitle', $html );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [
				$args['post_id'],
				$args['ticket']->name,
			]
		);

		$driver->setTolerableDifferencesPrefixes( [
			'Test ticket for ',
		] );

		$html = str_replace(
			[
				$args['post_id'],
				$args['ticket']->ID,
			],
			[
				'[EVENT_ID]',
				'[TICKET_ID]',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_not_show_description_if_ticket_description_empty() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$args['ticket']->description = '';

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets--no-description', $html );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [
				$args['post_id'],
				$args['ticket']->name,
			]
		);

		$driver->setTolerableDifferencesPrefixes( [
			'Test ticket for ',
		] );

		$html = str_replace(
			[
				$args['post_id'],
				$args['ticket']->ID,
			],
			[
				'[EVENT_ID]',
				'[TICKET_ID]',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_not_show_description_if_filter_is_false() {
		$template = tribe( 'tickets.editor.template' );

		add_filter( 'tribe_tickets_show_description', '__return_false' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertContains( 'tribe-tickets--no-description', $html );
		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [
				$args['post_id'],
				$args['ticket']->name,
			]
		);

		$html = str_replace(
			[
				$args['post_id'],
				$args['ticket']->ID,
			],
			[
				'[EVENT_ID]',
				'[TICKET_ID]',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_title_block() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( [
				$args['post_id'],
				$args['ticket']->name,
			]
		);

		$driver->setTolerableDifferencesPrefixes( [
			'Test ticket for ',
		] );

		$html = str_replace(
			[
				$args['post_id'],
				$args['ticket']->ID,
			],
			[
				'[EVENT_ID]',
				'[TICKET_ID]',
			],
			$html
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
