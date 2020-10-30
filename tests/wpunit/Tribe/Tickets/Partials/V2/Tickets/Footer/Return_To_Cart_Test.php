<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Footer;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class Return_To_Cart_Test extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/footer/return-to-cart';

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

		$event_id = $this->factory()->event->create();

		$ticket_ids = $this->create_many_paypal_tickets( 1, $event_id );

		return [
			'provider'       => $provider,
			'post_id'        => $event_id,
			'test_ticket_id' => $ticket_ids[0],
			'is_mini'        => true,
			'cart_url'       => 'http://wordpress.test/cart/?foo',
			'checkout_url'   => 'http://wordpress.test/checkout/?bar',
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_mini() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'is_mini' => false,
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_cart_and_checkout_url_same() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'cart_url'     => 'http://wordpress.test/cart/?foo',
			'checkout_url' => 'http://wordpress.test/cart/?foo',
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_is_mini_and_different_cart_and_checkout_url() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$invoice_number = 'foo';

		/** @var \Tribe__Tickets__Commerce__PayPal__Cart__Unmanaged $cart */
		$cart = tribe( 'tickets.commerce.paypal.cart' );
		$cart->set_id( $invoice_number );
		$cart->add_item( $args['test_ticket_id'], 3 );
		$cart->save();

		// Make PayPal Gateway find our mocked items in cart.
		add_filter(
			'tribe_tickets_commerce_paypal_invoice_number',
			static function () use ( $invoice_number ) {
				return $invoice_number;
			}
		);

		// Filter Checkout URL (not Cart, since Checkout starts from there) to force it different.
		add_filter(
			'tribe_tickets_tribe-commerce_checkout_url',
			static function () use ( $args ) {
				return $args['checkout_url'];
			}
		);

		$html = $template->template( $this->partial_path, $args, false );

		// Check Cart URL is showing.
		$this->assertContains( 'href="https://www.paypal.com/cgi-bin/webscr/_cart', $html );

		$this->assertMatchesSnapshot( $html );
	}

}
