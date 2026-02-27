<?php

namespace Tribe\Tickets\Partials\V2\Tickets\Item;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Partials\V2TestCase;

class ContentTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item/content';

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

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $event->ID, $ids[0] );

		$available_count = $ticket->available();

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		return [
			'post_id'             => $event->ID,
			'ticket'              => $ticket,
			'provider'            => $provider,
			'provider_id'         => $provider->class_name,
			'has_tickets_on_sale' => true,
			'is_sale_past'        => false,
			'is_sale_future'      => true,
			'currency'            => tribe( 'tickets.commerce.currency' ),
			'is_mini'             => false,
			'is_modal'            => false,
			'submit_button_name'  => 'cart-button',
			'cart_url'            => 'http://wordpress.test/cart/?foo',
			'checkout_url'        => 'http://wordpress.test/checkout/?bar',
			'threshold'           => 0,
			'key'                 => 0,
			'show_unlimited'      => (bool) apply_filters( 'tribe_tickets_block_show_unlimited_availability', true, $available_count ),
			'available_count'     => $available_count,
			'is_unlimited'        => -1 === $available_count,
			'max_at_a_time'       => $handler->get_ticket_max_purchase( $ticket->ID ),
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_ticket_is_empty() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'ticket' => '',
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_has_ticket_object() {
		$template = tribe( 'tickets.editor.template' );

		$args = $this->get_default_args();
		$html = $template->template( $this->partial_path, $args, false );

		// Normalize variable IDs so snapshot is stable (avoids driver fragment alignment issues).
		$html = str_replace( [ $args['post_id'], $args['ticket']->ID ], '{{ID}}', $html );
		// Normalize price in amount span only (avoid replacing digits inside class names).
		$html = preg_replace( '/<span class="tribe-amount">[^<]+<\/span>/', '<span class="tribe-amount">{{PRICE}}</span>', $html );
		// Normalize whitespace (tabs to newlines) so snapshot matches across environments.
		$html = preg_replace( '/\t+/', "\n", $html );

		$driver = $this->get_html_output_driver();
		$this->assertMatchesSnapshot( $html, $driver );
	}
}
