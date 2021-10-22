<?php

namespace TEC\Tickets\Commerce\Partials\Checkout\Cart;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Utils\Price;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

use Tribe__Tickets__Tickets;

class CartTest extends V2CommerceTestCase {

	// @todo @bordoni: We need to implement post remapping instead of the ticket maker.
	// Something like we did for views v2.
	use PayPal_Ticket_Maker;

	public $partial_path = 'checkout/cart';

	private $tolerables = [];

	/**
	 * Get all the default args required for this template
	 *
	 * @return array
	 */
	public function get_default_args() {
		$provider = tribe( Module::class );

		$event_id = $this->factory()->event->create( [
			'post_title' => 'Test event for partial snapshot',
		] );

		$ids = $this->create_many_paypal_tickets( 2, $event_id, [ 'price' => 97 ] );

		$this->tolerables[] = $event_id;
		$items = [];
		foreach ( $ids as $ticket_id ) {

			$ticket_obj = $provider->get_ticket( $event_id, $ticket_id );

			$quantity = 1;

			$items[ $ticket_id ] = [
				'ticket_id' => $ticket_id,
				'obj'       => $ticket_obj,
				'quantity'  => $quantity,
				'event_id'  => $event_id,
				'sub_total' => Price::sub_total( $ticket_obj->price, $quantity ),
			];

			$this->tolerables[] = $ticket_id;
		}

		$merchant   = tribe( Merchant::class );
		$sections   = array_unique( array_filter( wp_list_pluck( $items, 'event_id' ) ) );
		$sub_totals = array_filter( wp_list_pluck( $items, 'sub_total' ) );

		$args = [
			'merchant'    => $merchant,
			'provider_id' => Module::class,
			'provider'    => $provider,
			'items'       => $items,
			'sections'    => $sections,
			'section'     => $event_id,
			'total_value' => tribe_format_currency( Price::total( $sub_totals ) ),
		];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render_cart() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		// Handle variations that tolerances won't handle.
		foreach ( $args['items'] as $item ) {
			if ( ! empty( $item['event_id'] ) ) {
				$ticket_id = $item['ticket_id'];

				// Handle ticket ID variations that tolerances won't handle
				$html = str_replace(
					[
						get_the_permalink( $item['event_id'] ),
						'"'.$item['event_id'].'"',
						' '.$item['event_id'].'',
						'[' . $ticket_id . ']',
						'post-' . $ticket_id,
						'"' . $ticket_id . '"',
						'--' . $ticket_id . '',
					],
					[
						'http://wordpress.test/?tribe_events=event-test_event',
						'[EVENT_ID]',
						' [EVENT_ID]',
						'[TICKET_ID]',
						'post-TICKET_ID',
						'"TICKET_ID"',
						'--TICKET_ID',
					],
					$html
				);
			}
		}

		$driver->setTolerableDifferences( $this->tolerables );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
