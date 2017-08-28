<?php
namespace Tribe\Tickets\Commerce\PayPal;

use Prophecy\Argument;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Handler__IPN as IPN;
use Tribe__Tickets__Tickets_View as Tickets_View;

class TransactionTest extends \Codeception\TestCase\WPTestCase {

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
		add_filter (
			'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		}
		);

		// let's avoid confirmation emails
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );
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
	 * It should convert a flat array into a nested array with a ticket
	 *
	 * @test
	 */
	public function it_should_parse_transaction_into_nested_array_with_ticket() {
		/** @var \wpdb $wpdb */
		global $wpdb;

		$event_id = $this->factory()->post->create(
			[
				'meta_input' => [
					'_EventStartDate' => date( 'Y-m-d 08:00:00', strtotime( '+1 day' ) ),
					'_EventEndDate'   => date( 'Y-m-d 10:00:00', strtotime( '+1 day' ) ),
				],
			]
		);

		// we have a ticket product in the database
		$ticket_id = $this->factory()->post->create(
			[
				'post_type' => tribe( 'tickets.commerce.paypal' )->ticket_object,
				'meta_input' => [
					'_tribe_tpp_for_event' => $event_id,
					'_price'               => 10.00,
					'_stock'               => 100,
					'_manage_stock'        => 'yes',
					'_ticket_start_date'   => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
					'_ticket_end_date'     => date( 'Y-m-d H:i:s', strtotime( '+1 day' ) ),
				],
			]
		);

		$data = [
			'payment_type'         => 'instant',
			'payment_date'         => 'Mon Aug 28 2017 07:59:21 GMT-0400 (EDT)',
			'payment_status'       => 'Completed',
			'payer_status'         => 'verified',
			'first_name'           => 'John',
			'last_name'            => 'Smith',
			'payer_email'          => 'buyer@paypalsandbox.com',
			'receiver_email'       => 'seller@paypalsandbox.com',
			'item_name1'           => 'something',
			'item_number1'         => "{$event_id}:{$ticket_id}",
			'quantity1'            => 1,
			'shipping'             => 0,
			'tax'                  => 0,
			'mc_currency'          => 'USD',
			'mc_fee'               => '0.44',
			'mc_gross'             => 10.00,
			'mc_gross_1'           => 10.00,
			'mc_handling'          => 0,
			'mc_handling1'         => 0,
			'mc_shipping'          => 0,
			'mc_shipping1'         => 0,
			'txn_type'             => 'cart',
			'txn_id'               => '1234',
		];

		$parsed_transaction = tribe( 'tickets.commerce.paypal.gateway' )->parse_transaction( $data );

		$this->assertArrayHasKey( 'items', $parsed_transaction, 'Transaction array has items' );
		$this->assertCount( 1, $parsed_transaction['items'], 'Transaction should have 1 item' );

		$item = current( $parsed_transaction['items'] );
		codecept_debug( $parsed_transaction );

		$this->assertArrayHasKey( 'quantity', $item, 'Item has quantity' );
		$this->assertArrayHasKey( 'item_name', $item, 'Item has name' );
		$this->assertArrayHasKey( 'item_number', $item, 'Item has an ID pair' );
		$this->assertEquals( 'Tribe__Tickets__Ticket_Object', get_class( $item['ticket'] ), 'Transaction should include a ticket object' );
	}
}