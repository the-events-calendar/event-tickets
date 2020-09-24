<?php

namespace Tribe\Tickets\Partials\V2\Tickets;

use Codeception\TestCase\WPTestCase;
use Spatie\Snapshots\MatchesSnapshots;
use tad\WP\Snapshots\WPHtmlOutputDriver;
use Tribe\Test\PHPUnit\Traits\With_Post_Remapping;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class FooterTest extends WPTestCase {

	use MatchesSnapshots;
	use With_Post_Remapping;
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
			'cart_url'                    => $provider->get_cart_url(),
			'checkout_url'                => $provider->get_checkout_url(),
		];
	}

	/**
	 * @test
	 */
	public function test_should_not_render_if_not_is_mini_and_empty_ticket_on_sale() {
		$template = tribe( 'tickets.editor.template' );

		$args = [
			'is_mini' => null,
			'tickets_on_sale' => [],
		];

		$html   = $template->template( $this->partial_path, $args, false );
		$driver = new WPHtmlOutputDriver( home_url(), 'http://wordpress.test' );

		$this->assertMatchesSnapshot( $html, $driver );
	}

}
