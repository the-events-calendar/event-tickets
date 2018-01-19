<?php
namespace Tribe\Tickets\Commerce\PayPal;

use Prophecy\Argument;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;
use Tribe__Tickets__Tickets_View as Tickets_View;

class GatewayTest extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var Tickets_View
	 */
	protected $tickets_view;

	public function setUp() {
		// before
		parent::setUp();

		// your set up methods here
		$this->tickets_view = new Tickets_View();

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		// let's avoid confirmation emails
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

		tribe_update_option( 'ticket-enabled-post-types', [
			'tribe_events',
			'post',
		] );
	}

	public function dont_die() {
		// no-op, go on
	}

	public function tearDown() {
		// your tear down methods here

		// then
		parent::tearDown();
	}

	private function make_instance() {
		/** @var RSVP $instance */
		$instance = ( new \ReflectionClass( PayPal::class ) )->newInstanceWithoutConstructor();
		$instance->set_tickets_view( $this->tickets_view );

		return $instance;
	}

	/**
	 * Generates a ticket
	 *
	 * @param $event_id
	 * @param $price
	 *
	 * @return mixed
	 */
	protected function make_ticket( $event_id, $price ) {
		$ticket_id = $this->factory()->post->create(
			[
				'post_title' => "Test Ticket for {$event_id}",
				'post_type'  => tribe( 'tickets.commerce.paypal' )->ticket_object,
				'meta_input' => [
					'_tribe_tpp_for_event' => $event_id,
					'_price'               => $price,
					'_stock'               => 100,
					'_manage_stock'        => 'yes',
					'_ticket_start_date'   => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
					'_ticket_end_date'     => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
				],
			]
		);

		return $ticket_id;
	}

	/**
	 * It should construct a proper cart item name
	 *
	 * @test
	 */
	public function it_should_construct_a_proper_cart_item_name() {
		$event_id = $this->factory()->post->create(
			[
				'post_title' => 'Test Event',
				'meta_input' => [
					'_EventStartDate' => date( 'Y-m-d 08:00:00', strtotime( '+1 day' ) ),
					'_EventEndDate'   => date( 'Y-m-d 10:00:00', strtotime( '+1 day' ) ),
				],
			]
		);

		$event          = get_post( $event_id );
		$ticket_id      = $this->make_ticket( $event_id, 5.00 );
		$ticket         = tribe( 'tickets.commerce.paypal' )->get_ticket( $event_id, $ticket_id );
		$cart_item_name = tribe( 'tickets.commerce.paypal.gateway' )->get_product_name( $ticket, $event );

		$this->assertEquals( "{$ticket->name} - {$event->post_title}", $cart_item_name, 'Cart item name should concatenate ticket and post titles' );
	}

	/**
	 * It should use the sandbox URL when sandbox is enabled
	 *
	 * @test
	 */
	public function it_should_use_the_sandbox_url_when_sandbox_is_enabled() {
		tribe_update_option( 'ticket-paypal-sandbox', 1 );

		$this->assertEquals( 'https://www.sandbox.paypal.com/cgi-bin/webscr/', tribe( 'tickets.commerce.paypal.gateway' )->get_cart_url(), 'The sandbox URL should be present' );
	}

	/**
	 * It should use the production URL when sandbox is disabled
	 *
	 * @test
	 */
	public function it_should_use_the_production_url_when_sandbox_is_disabled() {
		tribe_update_option( 'ticket-paypal-sandbox', 0 );

		$this->assertEquals( 'https://www.paypal.com/cgi-bin/webscr/', tribe( 'tickets.commerce.paypal.gateway' )->get_cart_url(), 'The sandbox URL should be present' );
	}
}