<?php
namespace Tribe\Tickets\Commerce\PayPal;

use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker;
use Tribe__Post_Transient as Post_Transient;
use Tribe__Tickets__Commerce__PayPal__Gateway as Gateway;
use Tribe__Tickets__Commerce__PayPal__Handler__PDT as PDT;
use Tribe__Tickets__Commerce__PayPal__Main as PayPal;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Commerce__PayPal__Tickets_View as Tickets_View;
use Tribe__Tickets__Data_API as Data_API;

class PayPalTest extends \Codeception\TestCase\WPTestCase {

	use Attendee_Maker;
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

		// Enable Tribe Commerce.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules['Tribe__Tickets__Commerce__PayPal__Main'] = tribe( 'tickets.commerce.paypal' )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
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

		$sut = $this->make_instance();

		/** @var Gateway $gateway */
		$gateway = tribe( 'tickets.commerce.paypal.gateway' );

		/** @var PDT $pdt */
		$pdt = tribe( 'tickets.commerce.paypal.handler.pdt' );

		$data = $pdt->parse_transaction_body( $body );

		$gateway->set_raw_transaction_data( $data );

		$parsed_transaction = $gateway->parse_transaction( $data );

		$gateway->set_transaction_data( $parsed_transaction );
		$sut->generate_tickets();

		/** @var Post_Transient $post_transient */
		$post_transient = tribe( 'post-transient' );

		$post_transient->delete( $event_1_id, Tickets::ATTENDEES_CACHE );
		$post_transient->delete( $event_2_id, Tickets::ATTENDEES_CACHE );

		$attendees_event_1 = tribe_tickets_get_attendees( $event_1_id );

		$this->assertCount( 2, $attendees_event_1, 'Attendee count for the event 1 should be 2' );

		$attendees_event_2 = tribe_tickets_get_attendees( $event_2_id );

		$this->assertCount( 1, $attendees_event_2, 'Attendee count for the event 2 should be 2' );
	}

	/**
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( PayPal::class, $sut );
	}

	/**
	 * @return PayPal
	 * @throws \ReflectionException
	 */
	private function make_instance() {
		/** @var PayPal $instance */
		$instance = ( new \ReflectionClass( PayPal::class ) )->newInstance();
		$instance->set_tickets_view( $this->tickets_view );

		return $instance;
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_all_attendees_by_attendee_id().
	 */
	public function it_should_return_attendees_from_get_all_attendees_by_attendee_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_id = $test_attendee['attendee_id'];

		$attendees = $sut->get_all_attendees_by_attendee_id( $attendee_id );
	}

	/**
	 * @test
	 *
	 * It should return attendee from get_attendee().
	 */
	public function it_should_return_attendee_from_get_attendee() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_formatted = $sut->get_attendee( $test_attendee['attendee_id'], $post_id );

		$this->assertArrayHasKey( 'optout', $attendee_formatted );
		$this->assertArrayHasKey( 'ticket', $attendee_formatted );
		$this->assertArrayHasKey( 'attendee_id', $attendee_formatted );
		$this->assertArrayHasKey( 'security', $attendee_formatted );
		$this->assertArrayHasKey( 'product_id', $attendee_formatted );
		$this->assertArrayHasKey( 'check_in', $attendee_formatted );
		$this->assertArrayHasKey( 'order_status', $attendee_formatted );
		$this->assertArrayHasKey( 'user_id', $attendee_formatted );
		$this->assertArrayHasKey( 'ticket_sent', $attendee_formatted );

		$this->assertArrayHasKey( 'holder_name', $attendee_formatted );
		$this->assertArrayHasKey( 'holder_email', $attendee_formatted );
		$this->assertArrayHasKey( 'order_id', $attendee_formatted );
		$this->assertArrayHasKey( 'order_hash', $attendee_formatted );
		$this->assertArrayHasKey( 'ticket_id', $attendee_formatted );
		$this->assertArrayHasKey( 'qr_ticket_id', $attendee_formatted );
		$this->assertArrayHasKey( 'security_code', $attendee_formatted );

		$this->assertArrayHasKey( 'attendee_meta', $attendee_formatted );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_array().
	 */
	public function it_should_return_attendees_from_get_attendees_array() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$attendees = $sut->get_attendees_array( $post_id );

		$this->assertCount( 10, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_by_id().
	 */
	public function it_should_return_attendees_from_get_attendees_by_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$attendees = $sut->get_attendees_by_id( $post_id );

		$this->assertCount( 10, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_by_id() for order ID.
	 */
	public function it_should_return_attendees_from_get_attendees_by_id_for_order_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$order_id = $test_attendee['order_hash'];

		$attendees = $sut->get_attendees_by_id( $order_id );

		$this->assertCount( 10, $attendees );
		$this->assertEquals( $test_attendees, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_attendees_by_id() for ticket ID.
	 */
	public function it_should_return_attendees_from_get_attendees_by_id_for_ticket_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$attendees = $sut->get_attendees_by_id( $ticket_id );

		$this->assertCount( 10, $attendees );
	}

	/**
	 * @test
	 *
	 * It should return attendees from get_event_id_from_attendee_id() for attendee ID.
	 */
	public function it_should_return_event_id_from_get_event_id_from_attendee_id() {
		$sut = $this->make_instance();

		$base_data = $this->make_base_data();

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$this->create_many_attendees_for_ticket( 10, $ticket_id, $post_id );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_id = $test_attendee['attendee_id'];

		$event_id = $sut->get_event_id_from_attendee_id( $attendee_id );

		$this->assertEquals( $post_id, $event_id );
	}

	/**
	 * @return mixed
	 */
	protected function setup_POST( $status, $sales ) {
		$_POST['tickets_process']       = true;
		$_POST['attendee']              = [
			'email'        => 'me@tri.be',
			'full_name'    => 'Me',
			'order_status' => $status
		];
		$post_id                        = $this->factory()->post->create();
		$ticket_id                      = $this->make_sales_ticket( $sales, $post_id );
		$_POST["quantity_{$ticket_id}"] = 1;

		return $ticket_id;
	}

	protected function unset_POST() {
		unset( $_POST['tickets_process'] );
		unset( $_POST['attendee'] );
		unset( $_POST['product_id'] );
		// quantity_ID not relevant
	}

	protected function make_base_data( $sales = 0, $stock = 10, $type = 'base' ) {
		$post_id = $this->factory()->post->create();

		if ( 'sales' === $type ) {
			$ticket_id = $this->make_sales_ticket( $sales, $post_id );
		} elseif ( 'stock' === $type ) {
			$ticket_id = $this->make_stock_ticket( $stock, $post_id );
		} else {
			$ticket_id = $this->create_paypal_ticket( $post_id, 2, [
				'meta_input' => [
					'total_sales' => $sales,
					'_stock'      => $stock,
				],
			] );
		}

		$user_id = $this->factory()->user->create();

		return [
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
			'user_id'   => $user_id,
		];
	}

	protected function make_data( $previous_status, $status, $sales, $stock = 0, $type = 'base' ) {
		$base_data = $this->make_base_data( $sales, $stock, $type );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		$sut = $this->make_instance();

		// mock the already placed order
		$order_id = $this->factory()->post->create(
			[
				'meta_input' => [
					$sut->event_key => $previous_status,
				],
			]
		);

		// mock the current user
		wp_set_current_user( $user_id );

		// mock the submission data
		$data = [
			'email'        => 'me@tri.be',
			'full_name'    => 'Me',
			'order_status' => $status,
		];

		return [ $data, $ticket_id, $order_id, $post_id ];
	}

	public function order_filter_set_was_pending( $order ) {
		$reflection = new \ReflectionClass( get_class( $order ) );

		$property = $reflection->getProperty( 'was_pending' );
		$property->setAccessible( true );
		$property->setValue( $order, true );
	}

	/**
	 * @param int   $post_id          The event/post ID (parent of ticket).
	 * @param int   $ticket_id        The ticket post ID.
	 * @param int   $ticket_qty       The number of attendees that should be generated.
	 * @param array $attendee_details An array containing the details for the attendees
	 *                                that should be generated.
	 *
	 * @throws \ReflectionException
	 */
	protected function generate_tickets_for( $post_id, $ticket_id, $ticket_qty ) {
		$this->create_many_attendees_for_ticket( $ticket_qty, $ticket_id, $post_id );
	}

	protected function make_sales_ticket( $sales, $post_id ) {
		return $this->create_paypal_ticket( $post_id, 2, [
			'meta_input' => [
				'total_sales' => $sales,
				'_stock'      => $sales + 10,
			],
		] );
	}

	protected function make_stock_ticket( $stock, $post_id ) {
		return $this->create_paypal_ticket( $post_id, 2, [
			'meta_input' => [
				'_stock' => $stock,
			],
		] );
	}

	protected function fake_attendee_details( array $overrides = [] ) {
		static $order_id = 0;

		$order_id ++;

		return array_merge( [
			'full_name'    => 'Jane Doe',
			'email'        => 'jane@doe.com',
			'order_status' => 'yes',
			'optout'       => 'no',
			'order_id'     => $order_id,
		], $overrides );
	}
}