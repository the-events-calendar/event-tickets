<?php

namespace Tribe\Tickets;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Tickets\Commerce\Module;

/**
 * Test Attendees_List class.
 *
 * @since 5.6.2
 *
 * @covers \Tribe\Tickets\Events\Attendees_List
 */
class Attendees_ListTest extends \Codeception\TestCase\WPTestCase{
	use RSVP_Ticket_Maker;
	use Ticket_Maker;
	use Attendee_Maker;
	use Order_Maker;
	use With_Uopz;

	public function setUp(): void {
		parent::setUp();

		// Enable post as ticket type.
		add_filter( 'tribe_tickets_post_types', function () {
			return [ 'post' ];
		} );

		// Enable Tickets Commerce as the default provider.
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[Module::class] = Module::class;
			return $modules;
		} );
	}

	/**
	 * @test
	 *
	 * @covers \Tribe\Tickets\Events\Attendees_List::get_attendance_counts
	 */
	public function test_get_attendance_count_for_rsvp_attendees() {
		$post_id = $this->factory()->post->create();

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
		$attendees = $this->create_many_attendees_for_ticket( 5, $rsvp_ticket_id, $post_id );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$this->assertEquals( count($attendees), $attendees_list->get_attendance_counts( $post_id ) );
	}

	/**
	 * @test
	 *
	 * @covers \Tribe\Tickets\Events\Attendees_List::get_attendance_counts
	 */
	public function test_get_attendance_count_for_tickets_commerce_attendees() {
		$post_id = $this->factory()->post->create();

		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );
		$attendees = $this->create_many_attendees_for_ticket( 5, $tickets_commerce_ticket_id, $post_id );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$this->assertEquals( count($attendees), $attendees_list->get_attendance_counts( $post_id ) );
	}

	/**
	 * @test
	 *
	 * @covers \Tribe\Tickets\Events\Attendees_List::get_attendance_counts
	 */
	public function test_get_attendance_count_for_both_rsvp_and_tickets_commerce_attendees() {
		$post_id = $this->factory()->post->create();

		$rsvp_ticket_id = $this->create_rsvp_ticket( $post_id );
		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );

		$rsvp_attendees = $this->create_many_attendees_for_ticket( 16, $rsvp_ticket_id, $post_id );
		$ticket_attendees = $this->create_many_attendees_for_ticket( 5, $tickets_commerce_ticket_id, $post_id );

		/** @var \Tribe\Tickets\Events\Attendees_List $attendees_list */
		$attendees_list = tribe( 'tickets.events.attendees-list' );

		$total_attendees = count( $rsvp_attendees ) + count( $ticket_attendees );
		$this->assertEquals( $total_attendees, $attendees_list->get_attendance_counts( $post_id ) );
	}

	/**
	 * @test
	 */
	public function test_generated_attendee_has_post_title() {
		$post_id = $this->factory()->post->create();
		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );
		$ticket_attendees = $this->create_many_attendees_for_ticket( 1, $tickets_commerce_ticket_id, $post_id );

		$attendee = tec_tc_attendees()->by( 'event_id', $post_id )->first();

		$this->assertNotEmpty( $attendee );
		$this->assertNotEmpty( $attendee->post_title );
	}

	/**
	 * @test
	 */
	public function test_tribe_attendees_orm_should_avoid_pending_order_attendees() {
		$post_id = $this->factory()->post->create();
		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );

		$cart = new Cart();
		$cart->get_repository()->add_item( $tickets_commerce_ticket_id, 5 );

		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-'.uniqid().'@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$pending   = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );
		$cart->clear_cart();

		$attendee = tribe_attendees()->by( 'event_id', $post_id )->by( 'order_status', [ 'completed' ] )->count();
		$this->assertEquals( 0, $attendee );
	}

	/**
	 * @test
	 */
	public function test_tribe_attendees_orm_should_filter_attendees_by_order_statuses() {
		$post_id = $this->factory()->post->create();
		$tickets_commerce_ticket_id = $this->create_tc_ticket( $post_id );

		$this->set_class_fn_return( 'Tribe__Tickets__REST__V1__Main', 'request_has_valid_api_key', true );

		// create 5 attendees with pending order status.
		$cart = new Cart();
		$cart->get_repository()->add_item( $tickets_commerce_ticket_id, 5 );
		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-'.uniqid().'@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$pending   = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );
		$cart->clear_cart();

		// fetch the attendees with pending order status.
		$attendee = tribe_attendees()->by( 'event_id', $post_id )->by( 'order_status', [ 'pending' ] )->count();
		$this->assertEquals( 5, $attendee );

		// create 5 attendees with completed order status.
		$cart = new Cart();
		$cart->get_repository()->add_item( $tickets_commerce_ticket_id, 5 );
		$purchaser = [
			'purchaser_user_id'    => 0,
			'purchaser_full_name'  => 'Test Purchaser',
			'purchaser_first_name' => 'Test',
			'purchaser_last_name'  => 'Purchaser',
			'purchaser_email'      => 'test-'.uniqid().'@test.com',
		];

		$order     = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser );
		$pending   = tribe( Order::class )->modify_status( $order->ID, Pending::SLUG );
		$complete  = tribe( Order::class )->modify_status( $order->ID, Completed::SLUG );
		$cart->clear_cart();

		// fetch the attendees with completed order status.
		$attendee = tribe_attendees()->by( 'event_id', $post_id )->by( 'order_status', [ 'completed' ] )->count();
		$this->assertEquals( 5, $attendee );

		// fetch the attendees with completed and pending order status.
		$attendee = tribe_attendees()->by( 'event_id', $post_id )->by( 'order_status', [ 'completed', 'pending' ] )->count();
		$this->assertEquals( 10, $attendee );

		// fetch the attendees with completed and pending order status.
		$attendee = tribe_attendees()->by( 'event_id', $post_id )->count();
		$this->assertEquals( 10, $attendee );
	}
}
