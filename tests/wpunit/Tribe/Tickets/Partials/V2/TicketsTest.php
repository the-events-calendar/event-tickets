<?php

namespace Tribe\Tickets\Partials\V2;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class TicketsTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets';

	private $tolerables = [];
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
		$ids   = $this->create_many_paypal_tickets( 2, $event->ID, [ 'price' => 99 ] );

		$tickets = [];
		$this->tolerables[] = $event->ID;
		foreach ( $ids as $id ) {
			$tickets[] = $provider->get_ticket( $event->ID, $id );
			$this->tolerables[] = $id;
		}

		return [
			'post_id'                     => $event->ID,
			'provider'                    => $provider,
			'provider_id'                 => $provider->class_name,
			'tickets'                     => $tickets,
			'tickets_on_sale'             => $tickets,
			'has_tickets_on_sale'         => true,
			'is_sale_past'                => false,
			'is_sale_future'              => true,
			'currency'                    => tribe( 'tickets.commerce.currency' ),
			'threshold'                   => 0,
			'must_login'                  => false,
			'show_original_price_on_sale' => true,
			'is_mini'                     => false,
			'is_modal'                    => false,
			'submit_button_name'          => 'cart-button',
			'cart_url'                    => 'http://wordpress.test/cart/?foo',
			'checkout_url'                => 'http://wordpress.test/checkout/?bar',
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_sale_future() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_sale_future' => false,
			'tickets'        => [],
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_empty_provider() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_sale_future' => false,
			'provider'       => false,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_empty_tickets() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_sale_future' => false,
			'tickets'        => [],
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_regular_tickets_block() {
		$template = tribe( 'tickets.editor.template' );

		$override = [];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( $this->tolerables );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_ticket_block_for_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( $this->tolerables );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_ticket_block_for_is_modal() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_modal' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( $this->tolerables );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
