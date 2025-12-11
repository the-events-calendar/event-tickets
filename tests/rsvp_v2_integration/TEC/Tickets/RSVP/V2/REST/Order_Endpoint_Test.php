<?php

namespace TEC\Tickets\RSVP\V2\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Attendee as TC_Attendee;
use TEC\Tickets\RSVP\V2\Attendee;
use TEC\Tickets\RSVP\V2\Meta;
use TEC\Tickets\Test\Commerce\RSVP\V2\Attendee_Maker;
use TEC\Tickets\Test\Commerce\RSVP\V2\Ticket_Maker;
use WP_REST_Request;

/**
 * Tests for the RSVP V2 Order REST Endpoint.
 *
 * Note: Tests that require Order::create() are skipped in rsvp_v2_integration
 * since the full ORM (tec_tc_orders) is not available in this test environment.
 *
 * @since TBD
 */
class Order_Endpoint_Test extends WPTestCase {
	use Ticket_Maker;
	use Attendee_Maker;

	/**
	 * @before
	 */
	public function set_up_hooks(): void {
		// Register REST endpoints via the rest_api_init action.
		add_action( 'rest_api_init', function() {
			$hooks = tribe( \TEC\Tickets\RSVP\V2\Hooks::class );
			$hooks->register_rest_endpoints();
		} );

		// Fire the rest_api_init action.
		do_action( 'rest_api_init' );
	}

	/**
	 * @group requires-orm
	 */
	public function test_create_order_with_valid_data(): void {
		$this->markTestSkipped( 'Requires full ORM setup (tec_tc_orders) - test in wpunit suite.' );
	}

	public function test_create_order_with_invalid_ticket(): void {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/order' );
		$request->set_param( 'ticket_id', 999999 );
		$request->set_param( 'quantity', 1 );
		$request->set_param( 'purchaser', [
			'name'  => 'John Doe',
			'email' => 'john@example.com',
		] );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'tec_tickets_rsvp_v2_invalid_ticket', $data['code'] );
	}

	/**
	 * @group requires-orm
	 */
	public function test_create_order_with_insufficient_capacity(): void {
		$this->markTestSkipped( 'Requires full ORM setup (tec_tc_orders) - test in wpunit suite.' );
	}

	public function test_create_order_without_purchaser(): void {
		$event_id  = static::factory()->post->create();
		$ticket_id = $this->create_rsvp_ticket( $event_id );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'quantity', 1 );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'rest_missing_callback_param', $data['code'] );
	}

	/**
	 * @group requires-orm
	 */
	public function test_create_order_with_not_going_status(): void {
		$this->markTestSkipped( 'Requires full ORM setup (tec_tc_orders) - test in wpunit suite.' );
	}

	public function test_update_attendee_status_from_going_to_not_going(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 10 ] );
		$order_id    = $this->create_mock_order();
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, Meta::STATUS_GOING );

		// Link attendee to order.
		update_post_meta( $attendee_id, TC_Attendee::$order_relation_meta_key, $order_id );

		$request = new WP_REST_Request( 'PUT', '/tribe/tickets/v1/rsvp/order/' . $attendee_id );
		$request->set_param( 'attendee_id', $attendee_id );
		$request->set_param( 'rsvp_status', Meta::STATUS_NOT_GOING );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $attendee_id, $data['attendee_id'] );
		$this->assertEquals( Meta::STATUS_NOT_GOING, $data['rsvp_status'] );

		// Verify status was actually changed.
		$attendee = tribe( Attendee::class );
		$status   = $attendee->get_status( $attendee_id );
		$this->assertEquals( Meta::STATUS_NOT_GOING, $status );
	}

	public function test_update_attendee_status_from_not_going_to_going(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 10 ] );
		$order_id    = $this->create_mock_order();
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, Meta::STATUS_NOT_GOING );

		// Link attendee to order.
		update_post_meta( $attendee_id, TC_Attendee::$order_relation_meta_key, $order_id );

		$request = new WP_REST_Request( 'PUT', '/tribe/tickets/v1/rsvp/order/' . $attendee_id );
		$request->set_param( 'attendee_id', $attendee_id );
		$request->set_param( 'rsvp_status', Meta::STATUS_GOING );

		$response = rest_do_request( $request );

		$this->assertEquals( 200, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertEquals( Meta::STATUS_GOING, $data['rsvp_status'] );

		// Verify status was actually changed.
		$attendee = tribe( Attendee::class );
		$status   = $attendee->get_status( $attendee_id );
		$this->assertEquals( Meta::STATUS_GOING, $status );
	}

	public function test_update_attendee_status_checks_capacity(): void {
		$event_id    = static::factory()->post->create();
		$ticket_id   = $this->create_rsvp_ticket( $event_id, [ 'capacity' => 0 ] ); // No capacity
		$order_id    = $this->create_mock_order();
		$attendee_id = $this->create_rsvp_attendee( $ticket_id, $event_id, Meta::STATUS_NOT_GOING );

		// Link attendee to order.
		update_post_meta( $attendee_id, TC_Attendee::$order_relation_meta_key, $order_id );

		$request = new WP_REST_Request( 'PUT', '/tribe/tickets/v1/rsvp/order/' . $attendee_id );
		$request->set_param( 'attendee_id', $attendee_id );
		$request->set_param( 'rsvp_status', Meta::STATUS_GOING );

		$response = rest_do_request( $request );

		$this->assertEquals( 500, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'tec_tickets_rsvp_v2_no_capacity', $data['code'] );
	}

	public function test_update_invalid_attendee(): void {
		$request = new WP_REST_Request( 'PUT', '/tribe/tickets/v1/rsvp/order/999999' );
		$request->set_param( 'attendee_id', 999999 );
		$request->set_param( 'rsvp_status', Meta::STATUS_GOING );

		$response = rest_do_request( $request );

		$this->assertEquals( 404, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'tec_tickets_rsvp_v2_invalid_attendee', $data['code'] );
	}

	/**
	 * @group requires-orm
	 */
	public function test_create_order_with_attendee_data(): void {
		$this->markTestSkipped( 'Requires full ORM setup (tec_tc_orders) - test in wpunit suite.' );
	}
}
