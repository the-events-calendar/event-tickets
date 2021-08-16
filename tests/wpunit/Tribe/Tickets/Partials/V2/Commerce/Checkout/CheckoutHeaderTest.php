<?php

namespace Tribe\Tickets\Partials\V2\Commerce\Checkout;

use Tribe\Tickets\Test\Partials\V2CommerceTestCase;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;

class CheckoutHeaderTest extends V2CommerceTestCase {

	use PayPal_Ticket_Maker;

	public $partial_path = 'checkout/header';

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

		$this->tolerables[] = $event_id;

		$sections[] = $event_id;

		$args = [
			'sections' => $sections,
		];

		return $args;
	}

	/**
	 * @test
	 */
	public function test_should_render() {
		$args   = $this->get_default_args();
		$html   = $this->template_class()->template( $this->partial_path, $args, false );
		$driver = $this->get_html_output_driver();


		$html = str_replace(
			get_the_permalink( $this->tolerables[0] ),
			'http://wordpress.test/?tribe_events=event-test_event',
			$html
		);

		$driver->setTolerableDifferences( $this->tolerables );

		$this->assertMatchesSnapshot( $html, $driver );
	}
}
