<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Footer;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class Return_To_Cart_Test extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/footer/return-to-cart';

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
			'checkout_url' => 'http://wordpress.test/cart/?bar', // Should only be comparing the part BEFORE the '?'.
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * We need to filter both Cart and Checkout URLs because PayPal has the same for both, which is dynamic.
	 *
	 * @test
	 */
	public function test_should_render_if_is_mini_and_different_cart_and_checkout_url() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

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

		$html = $template->template( $this->partial_path, $args, false );

		// Check Cart URL is showing.
		$this->assertContains( $args['cart_url'], $html );

		$this->assertMatchesSnapshot( $html );
	}

}
