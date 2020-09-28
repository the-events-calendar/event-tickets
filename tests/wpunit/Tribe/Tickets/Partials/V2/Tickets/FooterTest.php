<?php

namespace Tribe\Tickets\Partials\V2\Tickets;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class FooterTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/footer';

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

		$event   = $this->get_mock_event( 'events/single/1.json' );
		$tickets = $this->create_many_paypal_tickets( 3, $event->ID );

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
	public function test_should_not_render_if_not_is_mini_and_empty_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_mini' => false,
			'tickets_on_sale' => [],
		];

		$html   = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_ar_page_mini_cart_footer_if_is_mini_and_empty_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini'         => true,
			'tickets_on_sale' => [],
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html   = $template->template( $this->partial_path, $args, false );

		// Make sure we have the Return to Cart link shown.
		$this->assertContains( 'tribe-tickets__footer__back-link', $html );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_ticket_form_footer_if_not_is_mini_and_has_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini'         => false,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_ar_page_mini_cart_footer_with_cart_url_if_is_mini_and_has_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini'      => true,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		// Make sure we have the Return to Cart link shown.
		$this->assertContains( 'tribe-tickets__footer__back-link', $html );

		$this->assertMatchesSnapshot( $html );
	}

}
