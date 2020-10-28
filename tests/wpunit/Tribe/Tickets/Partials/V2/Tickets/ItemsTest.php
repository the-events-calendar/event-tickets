<?php

namespace Tribe\Tickets\Partials\V2\Tickets;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class ItemsTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	protected $partial_path = 'v2/tickets/items';

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
		$ids     = $this->create_many_paypal_tickets( 2, $event->ID, [ 'price' => 99 ] );
		$tickets = [];

		foreach ( $ids as $ticket ) {
			$tickets[] = $provider->get_ticket( $event->ID, $ticket );
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
			'is_mini'                     => false,
			'is_modal'                    => false,
			'submit_button_name'          => 'cart-button',
			'cart_url'                    => 'http://wordpress.test/cart/?foo',
			'checkout_url'                => 'http://wordpress.test/checkout/?bar',
			'threshold'                   => 0,
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_empty_tickets_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'tickets_on_sale' => [],
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_not_empty_tickets_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences(
			array_merge( [ $args['post_id'] ], wp_list_pluck( $args['tickets'], 'ID' ) )
		);

		$driver->setTimeDependentAttributes( [ 'value', 'data-ticket-id', 'aria-controls' ] );

		$driver->setTolerableDifferencesPrefixes( [
			'post-',
			'tribe-block-tickets-item-',
			'Test ticket for ',
			'Test ticket description for ',
			'tribe__details__content--',
			'class="tribe-amount">',
		] );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
