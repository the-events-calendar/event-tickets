<?php
namespace Tribe\Tickets\Commerce\PayPal;

use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe__Post_Transient as Post_Transient;
use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_View as Tickets_View;

class PayPalTest extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;

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

		add_filter( 'tribe_tickets_get_modules', function ( array $modules ) {
			$modules[ \Tribe__Tickets__Commerce__PayPal__Main::class ] = 'Tribe Commerce';

			return $modules;
		} );
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
	 * It should generate attendees for all tickets in PDT transaction
	 *
	 * @test
	 */
	public function it_should_generate_attendees_for_all_tickets_in_PDT_transaction() {
		add_filter( 'tribe_tickets_commerce_paypal_handler', function () {
			return 'pdt';
		} );

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
		$total_in_cart     = number_format( $ticket_1_gross + $ticket_2_gross, 2, '.', '' );

		// we have a ticket product in the database
		$ticket_1_id = $this->create_paypal_ticket( $event_1_id, $ticket_1_price );
		$ticket_2_id = $this->create_paypal_ticket( $event_2_id, $ticket_2_price );

		$body = <<<EOT
SUCCESS
mc_gross={$total_in_cart}
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
payment_gross={$total_in_cart}
EOT;

		tribe( 'tickets.data_api' );

		/** @var PayPal $paypal */
		$paypal = tribe( 'tickets.commerce.paypal' );

		/** @var Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		/** @var PDT $pdt */
		$pdt = tribe( 'tickets.commerce.paypal.handler.pdt' );

		$data = $pdt->parse_transaction_body( $body );

		$gateway->set_raw_transaction_data( $data );

		$parsed_transaction = $gateway->parse_transaction( $data );

		$gateway->set_transaction_data( $parsed_transaction );
		$paypal->generate_tickets();

		/** @var Post_Transient $post_transient */
		$post_transient = tribe( 'post-transient' );

		$post_transient->delete( $event_1_id, Tickets::ATTENDEES_CACHE );
		$post_transient->delete( $event_2_id, Tickets::ATTENDEES_CACHE );

		$attendees_event_1 = tribe_tickets_get_attendees( $event_1_id );

		$this->assertCount( 2, $attendees_event_1, 'Attendee count for the event 1 should be 2' );

		$attendees_event_2 = tribe_tickets_get_attendees( $event_2_id );

		$this->assertCount( 1, $attendees_event_2, 'Attendee count for the event 2 should be 2' );
	}
}