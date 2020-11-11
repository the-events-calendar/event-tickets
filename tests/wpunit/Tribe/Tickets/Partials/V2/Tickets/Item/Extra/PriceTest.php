<?php
namespace Tribe\Tickets\Partials\V2\Tickets\Item\Extra;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class PriceTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/extra/price';

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

		// default Ticket scenario.
		$ticket->on_sale = false;
		$ticket->price = $ticket->regular_price = 99;
		$ticket->price_suffix = '';

		return [
			'post_id'     => $event->ID,
			'ticket'      => $ticket,
			'provider'    => $provider,
			'provider_id' => $provider->class_name,
			'currency'    => tribe( 'tickets.commerce.currency' ),
		];
	}

	/**
	 * @test
	 */
	public function test_should_render_price_block_with_price_suffix() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		// Set price suffix.
		$args['ticket']->price_suffix = 'Great_Price';

		$html = $template->template( $this->partial_path, $args, false );

		// Make sure we have suffix block.
		$this->assertContains( 'tribe-tickets__tickets-sale-price-suffix', $html );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}

	/**
	 * @test
	 */
	public function test_should_render_price_block_for_regular_product() {
		$template = tribe( 'tickets.editor.template' );

		$html = $template->template( $this->partial_path, $this->get_default_args(), false );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}

	/**
	 * @test
	 */
	public function test_should_render_price_block_for_product_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();

		$args['ticket']->on_sale = true;
		$args['ticket']->price = 89;

		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html, $this->get_html_output_driver() );
	}
}
