<?php

namespace Tribe\Tickets\Commerce\PayPal;

use Tribe\Tickets\Test\Commerce\Test_Case;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Ticket_Maker as PayPal_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\PayPal\Order_Maker as PayPal_Order_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Commerce__PayPal__Main as Main;
use Tribe__Tickets__Data_API as Data_API;
use Prophecy\Argument;

class MainTest extends Test_Case {

	use Attendee_Maker;
	use PayPal_Ticket_Maker;
	use PayPal_Order_Maker;
	use RSVP_Ticket_Maker;

	public function setUp() {
		// before
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// let's avoid die()s
		add_filter( 'tribe_exit', function () {
			return [ $this, 'dont_die' ];
		} );

		// let's avoid confirmation emails
		add_filter( 'tribe_tickets_rsvp_send_mail', '__return_false' );

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
	 * @test
	 * it should be instantiatable
	 */
	public function it_should_be_instantiatable() {
		$sut = $this->make_instance();

		$this->assertInstanceOf( Main::class, $sut );
	}

	private function make_instance() {
		/** @var Main $instance */
		$instance = ( new \ReflectionClass( Main::class ) )->newInstanceWithoutConstructor();

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$generated = $this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

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

		$post_id        = $base_data['post_id'];
		$ticket_id      = $base_data['ticket_id'];
		$rsvp_ticket_id = $base_data['rsvp_ticket_id'];

		$this->create_paypal_orders( $post_id, $ticket_id, 10 );

		// Create RSVP attendees to ensure only the expected attendees get returned.
		$this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		$test_attendees = $sut->get_attendees_array( $post_id );

		$test_attendee = current( $test_attendees );

		$attendee_id = $test_attendee['attendee_id'];

		$event_id = $sut->get_event_id_from_attendee_id( $attendee_id );

		$this->assertEquals( $post_id, $event_id );
	}

	protected function make_data( $previous_status, $status, $sales, $stock = 0 ) {
		$base_data = $this->make_base_data( $sales, $stock );

		$post_id   = $base_data['post_id'];
		$ticket_id = $base_data['ticket_id'];
		$user_id   = $base_data['user_id'];

		// mock the already placed order
		$order_id = $this->factory()->post->create( [
			'meta_input' => [
				Main::ATTENDEE_RSVP_KEY => $previous_status,
			],
		] );

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

	protected function make_base_data( $sales = 0, $stock = 10 ) {
		$post_id = $this->factory()->post->create();

		$ticket_id = $this->create_paypal_ticket( $post_id, 1, [
			'meta_input' => [
				'total_sales' => $sales,
				'_stock'      => $stock,
				'_capacity'   => $stock + $sales,
			],
		] );

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id, [
			'meta_input' => [
				'total_sales' => $sales,
				'_stock'      => $stock,
				'_capacity'   => $stock + $sales,
			],
		] );

		$user_id = $this->factory()->user->create();

		return [
			'post_id'        => $post_id,
			'ticket_id'      => $ticket_id,
			'rsvp_ticket_id' => $rsvp_ticket_id,
			'user_id'        => $user_id,
		];
	}
}
