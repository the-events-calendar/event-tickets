<?php

namespace Tribe\Tickets\Partials\V2\Commerce\Checkout\Cart\Item;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Gateways\PayPal\Merchant;
use TEC\Tickets\Commerce\Utils\Price;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

use Tribe__Tickets__Tickets;

class ItemPriceTest extends V2CommerceTestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'checkout/cart/item/price';

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

		$ids = $this->create_many_paypal_tickets( 1, $event_id, [ 'price' => 99 ] );

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
			'provider'    => tribe( Module::class ),
			'items'       => $items,
			'item'        => $items[ $ids[0] ],
			'sections'    => $sections,
			'total_value' => tribe_format_currency( Price::total( $sub_totals ) ),
		];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render_cart_item_price() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences( $this->tolerables );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
