<?php

namespace Tribe\Tickets\Partials\V2\Tickets;

use Tribe\Tickets\Test\Partials\V2TestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class ItemTest extends V2TestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'v2/tickets/item';

	/**
	 * Get all the default args required for this template.
	 *
	 * @return array
	 */
	public function get_default_args() {

		/**
		 * @var \Tribe__Tickets__Commerce__PayPal__Main $provider
		 */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$event = $this->get_mock_event( 'events/single/1.json' );

		$ids = $this->create_many_paypal_tickets( 1, $event->ID, [ 'price' => 99 ] );

		/** @var \Tribe__Tickets__Ticket_Object $ticket */
		$ticket = $provider->get_ticket( $event->ID, $ids[0] );

		$available_count = $ticket->available();

		/** @var \Tribe__Tickets__Tickets_Handler $handler */
		$handler = tribe( 'tickets.handler' );

		$handler->has_shared_capacity( $ticket );

		$has_shared_cap = $handler->has_shared_capacity( $ticket );

		return [
			'post_id'             => $event->ID,
			'provider'            => $provider,
			'provider_id'         => $provider->class_name,
			'ticket'              => $ticket,
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
			'is_unlimited'        => - 1 === $available_count,
			'has_shared_cap'      => $has_shared_cap,
			'data_available'      => 0 === $handler->get_ticket_max_purchase( $ticket->ID ) ? 'false' : 'true',
			'data_has_shared_cap' => $has_shared_cap ? 'true' : 'false',
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_empty_provider() {
		$template = tribe( 'tickets.editor.template' );

		$override = [
			'provider' => '',
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_provider_not_same() {
		$template = tribe( 'tickets.editor.template' );

		// Altering the provider property to mock mismatch with Ticket's provider class name.
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$provider->class_name = 'random_class_name';

		$override = [
			'provider' => $provider,
		];

		$args = array_merge( $this->get_default_args(), $override );
		$html = $template->template( $this->partial_path, $args, false );

		$this->assertMatchesSnapshot( $html );
	}

	/**
	 * @test
	 */
	public function test_should_render_if_has_provider_and_provider_has_same_class_as_ticket_provider() {
		$template = tribe( 'tickets.editor.template' );

		/**
		 * Make sure we have the proper class set.
		 *
		 * @var \Tribe__Tickets__Commerce__PayPal__Main $provider
		 */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$provider->class_name = 'Tribe__Tickets__Commerce__PayPal__Main';

		$override = [
			'provider' => $provider,
		];

		$args = array_merge( $this->get_default_args(), $override );

		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences(
			[
				$args['post_id'],
				$args['ticket']->ID,
			]
		);

		$driver->setTolerableDifferencesPrefixes(
			[
				'post-',
				'tribe-block-tickets-item-',
				'Test ticket for ',
				'Test ticket description for ',
				'tribe__details__content--',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}

	/**
	 * @test
	 */
	public function test_should_render_wit_whatever_data_attribute() {
		$template = tribe( 'tickets.editor.template' );

		/**
		 * Make sure we have the proper class set.
		 *
		 * @var \Tribe__Tickets__Commerce__PayPal__Main $provider
		 */
		$provider = tribe_get_class_instance( 'Tribe__Tickets__Commerce__PayPal__Main' );

		$provider->class_name = 'Tribe__Tickets__Commerce__PayPal__Main';

		$override = [
			'provider' => $provider,
		];

		add_filter(
			'tribe_tickets_block_ticket_html_attributes',
			static function( $attributes ) {
				$attributes['data-whatever'] = 'value';
				return $attributes;
			}
		);

		$args = array_merge( $this->get_default_args(), $override );

		$html = $template->template( $this->partial_path, $args, false );

		$driver = $this->get_html_output_driver();

		$driver->setTolerableDifferences(
			[
				$args['post_id'],
				$args['ticket']->ID,
			]
		);

		$driver->setTolerableDifferencesPrefixes(
			[
				'post-',
				'tribe-block-tickets-item-',
				'Test ticket for ',
				'Test ticket description for ',
				'tribe__details__content--',
			]
		);

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
