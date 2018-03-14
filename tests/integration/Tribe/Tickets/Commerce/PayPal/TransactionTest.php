<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
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
		add_filter(
			'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		}
		);

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
	 * It should convert a flat array into a nested array with a ticket
	 *
	 * @test
	 */
	public function it_should_parse_successful_transaction_with_single_item_into_nested_array_with_ticket() {
		$event_id = $this->factory()->post->create(
			[
				'meta_input' => [
					'_EventStartDate' => date( 'Y-m-d 08:00:00', strtotime( '+1 day' ) ),
					'_EventEndDate'   => date( 'Y-m-d 10:00:00', strtotime( '+1 day' ) ),
				],
			]
		);

		// we have a ticket product in the database
		$ticket_id = $this->make_ticket( $event_id, 2.00 );

		$body = <<<EOT
SUCCESS
mc_gross=2.00
protection_eligibility=Eligible
address_status=confirmed
item_number1={$event_id}%3A{$ticket_id}
payer_id=C9AXBQWVQMWMJ
tax=0.00
address_street=1+Main+St
payment_date=11%3A00%3A53+Aug+28%2C+2017+PDT
payment_status=Completed
charset=windows-1252
address_zip=95131
mc_shipping=0.00
mc_handling=0.00
first_name=Test
mc_fee=0.36
address_country_code=US
address_name=Test+Buyer
custom=%7B%22user_id%22%3A1%2C%22tribe_handler%22%3A%22tpp%22%7D
payer_status=verified
business=seller%40paypalsandbox.com
address_country=United+States
num_cart_items=1
mc_handling1=0.00
address_city=San+Jose
payer_email=buyer%40paypalsandbox.com
mc_shipping1=0.00
tax1=0.00
txn_id=23G06877BY065820D
payment_type=instant
last_name=Buyer
address_state=CA
item_name1=Ticket1
receiver_email=seller%40paypalsandbox.com
payment_fee=0.36
shipping_discount=0.00
quantity1=1
insurance_amount=0.00
receiver_id=Q5E468BVGWNE4
txn_type=cart
discount=0.00
mc_gross_1=2.00
mc_currency=USD
residence_country=US
shipping_method=Default
transaction_subject=
payment_gross=2.00
EOT;

		$data = tribe( 'tickets.commerce.paypal.handler.pdt' )->parse_transaction_body( $body );

		$parsed_transaction = tribe( 'tickets.commerce.paypal.gateway' )->parse_transaction( $data );

		$this->assertArrayHasKey( 'items', $parsed_transaction, 'Transaction array has items' );
		$this->assertCount( 1, $parsed_transaction['items'], 'Transaction should have 1 item' );

		$item = reset( $parsed_transaction['items'] );

		$this->assertArrayHasKey( 'quantity', $item, 'Item has quantity' );
		$this->assertArrayHasKey( 'item_name', $item, 'Item has name' );
		$this->assertArrayHasKey( 'item_number', $item, 'Item has an ID pair' );
		$this->assertEquals( 'Tribe__Tickets__Ticket_Object', get_class( $item['ticket'] ), 'Transaction should include a ticket object' );
	}

	/**
	 * It should convert a flat array into a nested array with a ticket
	 *
	 * @test
	 */
	public function it_should_parse_successful_transaction_with_multiple_items_into_nested_array_with_ticket() {
		$event_id = $this->factory()->post->create(
			[
				'meta_input' => [
					'_EventStartDate' => date( 'Y-m-d 08:00:00', strtotime( '+1 day' ) ),
					'_EventEndDate'   => date( 'Y-m-d 10:00:00', strtotime( '+1 day' ) ),
				],
			]
		);

		$ticket_1_quantity = 2;
		$ticket_2_quantity = 1;
		$ticket_1_price    = 2.0;
		$ticket_2_price    = 5.0;
		$ticket_1_gross    = 4.0;
		$ticket_2_gross    = 5.0;
		$tickets_in_cart   = 3;

		// we have a ticket product in the database
		$ticket_1_id = $this->make_ticket( $event_id, $ticket_1_price );
		$ticket_2_id = $this->make_ticket( $event_id, $ticket_2_price );

		$body = <<<EOT
SUCCESS
mc_gross=9.00
protection_eligibility=Eligible
address_status=confirmed
item_number1={$event_id}%3A{$ticket_1_id}
item_number2={$event_id}%3A{$ticket_2_id}
payer_id=C9AXBQWVQMWMJ
tax=0.00
address_street=1+Main+St
payment_date=11%3A00%3A53+Aug+28%2C+2017+PDT
payment_status=Completed
charset=windows-1252
address_zip=95131
mc_shipping=0.00
mc_handling=0.00
first_name=Test
mc_fee=0.36
address_country_code=US
address_name=Test+Buyer
custom=%7B%22user_id%22%3A1%2C%22tribe_handler%22%3A%22tpp%22%7D
payer_status=verified
business=seller%40paypalsandbox.com
address_country=United+States
num_cart_items={$tickets_in_cart}
mc_handling1=0.00
address_city=San+Jose
payer_email=buyer%40paypalsandbox.com
mc_shipping1=0.00
tax1=0.00
txn_id=23G06877BY065820D
payment_type=instant
last_name=Buyer
address_state=CA
item_name1=Ticket1
item_name2=Ticket2
receiver_email=seller%40paypalsandbox.com
payment_fee=0.36
shipping_discount=0.00
quantity1={$ticket_1_quantity}
quantity2={$ticket_2_quantity}
insurance_amount=0.00
receiver_id=Q5E468BVGWNE4
txn_type=cart
discount=0.00
mc_gross_1={$ticket_1_gross}
mc_gross_2={$ticket_2_gross}
mc_currency=USD
residence_country=US
shipping_method=Default
transaction_subject=
payment_gross=9.00
EOT;


		$data = tribe( 'tickets.commerce.paypal.handler.pdt' )->parse_transaction_body( $body );

		$parsed_transaction = tribe( 'tickets.commerce.paypal.gateway' )->parse_transaction( $data );

		$this->assertArrayHasKey( 'items', $parsed_transaction, 'Transaction array has items' );
		$this->assertCount( 2, $parsed_transaction['items'], 'Transaction should have 1 item' );

		$item = reset( $parsed_transaction['items'] );

		$this->assertArrayHasKey( 'quantity', $item, 'Item has quantity' );
		$this->assertArrayHasKey( 'item_name', $item, 'Item has name' );
		$this->assertArrayHasKey( 'item_number', $item, 'Item has an ID pair' );
		$this->assertEquals( 'Tribe__Tickets__Ticket_Object', get_class( $item['ticket'] ), 'Transaction should include a ticket object' );

		$this->assertEquals( 9.0, $parsed_transaction['payment_gross'], 'Payment gross should be the sum of the item quantity*gross' );
		$this->assertEquals( 4.0, $item['mc_gross'], 'Payment gross of ticket 1 should be 4.00' );
		$this->assertEquals( $ticket_1_id, $item['ticket']->ID, 'Ticket 1 should have the correct ID' );

		$item = next( $parsed_transaction['items'] );

		$this->assertEquals( $ticket_2_id, $item['ticket']->ID, 'Ticket 2 should have the correct ID' );
		$this->assertEquals( 5.0, $item['mc_gross'], 'Payment gross of ticket 2 should be 5.00' );
	}

	/**
	 * It should parse transaction with tickets from multiple events
	 *
	 * @test
	 */
	public function it_should_parse_successful_transaction_with_tickets_from_multiple_events() {
		$event_1_id = $this->factory()->post->create(
			[
				'meta_input' => [
					'_EventStartDate' => date( 'Y-m-d 08:00:00', strtotime( '+1 day' ) ),
					'_EventEndDate'   => date( 'Y-m-d 10:00:00', strtotime( '+1 day' ) ),
				],
			]
		);

		$event_2_id = $this->factory()->post->create(
			[
				'meta_input' => [
					'_EventStartDate' => date( 'Y-m-d 08:00:00', strtotime( '+2 day' ) ),
					'_EventEndDate'   => date( 'Y-m-d 10:00:00', strtotime( '+2 day' ) ),
				],
			]
		);

		$ticket_1_quantity = 2;
		$ticket_2_quantity = 1;
		$ticket_1_price    = 2.0;
		$ticket_2_price    = 5.0;
		$ticket_1_gross    = 4.0;
		$ticket_2_gross    = 5.0;
		$tickets_in_cart   = 3;

		// we have a ticket product in the database
		$ticket_1_id = $this->make_ticket( $event_1_id, $ticket_1_price );
		$ticket_2_id = $this->make_ticket( $event_2_id, $ticket_2_price );

		$body = <<<EOT
SUCCESS
mc_gross=9.00
protection_eligibility=Eligible
address_status=confirmed
item_number1={$event_1_id}%3A{$ticket_1_id}
item_number2={$event_2_id}%3A{$ticket_2_id}
payer_id=C9AXBQWVQMWMJ
tax=0.00
address_street=1+Main+St
payment_date=11%3A00%3A53+Aug+28%2C+2017+PDT
payment_status=Completed
charset=windows-1252
address_zip=95131
mc_shipping=0.00
mc_handling=0.00
first_name=Test
mc_fee=0.36
address_country_code=US
address_name=Test+Buyer
custom=%7B%22user_id%22%3A1%2C%22tribe_handler%22%3A%22tpp%22%7D
payer_status=verified
business=seller%40paypalsandbox.com
address_country=United+States
num_cart_items={$tickets_in_cart}
mc_handling1=0.00
address_city=San+Jose
payer_email=buyer%40paypalsandbox.com
mc_shipping1=0.00
tax1=0.00
txn_id=23G06877BY065820D
payment_type=instant
last_name=Buyer
address_state=CA
item_name1=Ticket1
item_name2=Ticket2
receiver_email=seller%40paypalsandbox.com
payment_fee=0.36
shipping_discount=0.00
quantity1={$ticket_1_quantity}
quantity2={$ticket_2_quantity}
insurance_amount=0.00
receiver_id=Q5E468BVGWNE4
txn_type=cart
discount=0.00
mc_gross_1={$ticket_1_gross}
mc_gross_2={$ticket_2_gross}
mc_currency=USD
residence_country=US
shipping_method=Default
transaction_subject=
payment_gross=9.00
EOT;


		$data = tribe( 'tickets.commerce.paypal.handler.pdt' )->parse_transaction_body( $body );

		$parsed_transaction = tribe( 'tickets.commerce.paypal.gateway' )->parse_transaction( $data );

		$this->assertArrayHasKey( 'items', $parsed_transaction, 'Transaction array has items' );
		$this->assertCount( 2, $parsed_transaction['items'], 'Transaction should have 1 item' );

		$item = reset( $parsed_transaction['items'] );

		$this->assertArrayHasKey( 'quantity', $item, 'Item has quantity' );
		$this->assertArrayHasKey( 'item_name', $item, 'Item has name' );
		$this->assertArrayHasKey( 'item_number', $item, 'Item has an ID pair' );
		$this->assertEquals( 'Tribe__Tickets__Ticket_Object', get_class( $item['ticket'] ), 'Transaction should include a ticket object' );

		$this->assertEquals( 9.0, $parsed_transaction['payment_gross'], 'Payment gross should be the sum of the item quantity*gross' );
		$this->assertEquals( 4.0, $item['mc_gross'], 'Payment gross of ticket 1 should be 4.00' );
		$this->assertEquals( $ticket_1_id, $item['ticket']->ID, 'Ticket 1 should have the correct ID' );

		$item = next( $parsed_transaction['items'] );

		$this->assertEquals( $ticket_2_id, $item['ticket']->ID, 'Ticket 2 should have the correct ID' );
		$this->assertEquals( 5.0, $item['mc_gross'], 'Payment gross of ticket 2 should be 5.00' );
	}
}