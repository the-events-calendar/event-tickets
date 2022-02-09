<?php

namespace Tribe\Tickets\Partials\V2;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class TicketsTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets';

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

		$event_id = $this->factory()->event->create( [
			'post_title' => 'Test event for partial snapshot',
		] );

		$ids = $this->create_many_paypal_tickets( 2, $event_id, [ 'price' => 99 ] );

		$tickets            = [];
		$this->tolerables[] = $event_id;
		foreach ( $ids as $id ) {
			$tickets[] = $provider->get_ticket( $event_id, $id );

			$this->tolerables[] = $id;
		}

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $event_id, $ids[0] );

		$available_count = $ticket->available();

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$args = [
			'test_ticket_id'              => $ids[0],
			'post_id'                     => $event_id,
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
			'handler'                     => $handler,
			'show_unlimited'              => (bool) apply_filters( 'tribe_tickets_block_show_unlimited_availability', true, $available_count ),
			'available_count'             => $available_count,
			'is_unlimited'                => - 1 === $available_count,
			'max_at_a_time'               => $handler->get_ticket_max_purchase( $ticket->ID ),
		];

		// Filter PayPal Cart URL.
		add_filter(
			'tribe_tickets_tribe-commerce_cart_url',
			static function () use ( $args ) {
				return $args['cart_url'];
			}
		);

		// Filter PayPal Checkout URL.
		add_filter(
			'tribe_tickets_tribe-commerce_checkout_url',
			static function () use ( $args ) {
				return $args['checkout_url'];
			}
		);

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_sale_future() {
		/** @var \Tribe__Tickets__Editor__Template $template */
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
		/** @var \Tribe__Tickets__Editor__Template $template */
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
		/** @var \Tribe__Tickets__Editor__Template $template */
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
		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$override = [];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( [ $this->tolerables ] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_ticket_block_for_is_mini() {
		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( [ $this->tolerables ] );

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_ticket_block_for_is_modal() {
		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_modal' => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();
		$driver->setTolerableDifferences( [ $this->tolerables ] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
