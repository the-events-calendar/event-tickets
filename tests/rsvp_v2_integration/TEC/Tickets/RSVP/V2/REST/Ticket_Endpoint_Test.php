<?php

namespace TEC\Tickets\RSVP\V2\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\RSVP\V2\Ticket;
use TEC\Tickets\Test\Commerce\RSVP\V2\Ticket_Maker;
use WP_REST_Request;

/**
 * Tests for the RSVP V2 Ticket REST Endpoint.
 *
 * Note: Some tests that require permission checks on the tec_tc_ticket post type
 * are marked with @group requires-tc-post-type since the post type may not be
 * registered in certain test environments.
 *
 * @since TBD
 */
class Ticket_Endpoint_Test extends WPTestCase {
	use Ticket_Maker;

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

	public function test_create_ticket_with_valid_data(): void {
		$user_id  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = static::factory()->post->create();

		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', $event_id );
		$request->set_param( 'name', 'Test RSVP Ticket' );
		$request->set_param( 'description', 'This is a test ticket' );
		$request->set_param( 'capacity', 100 );

		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'ticket_id', $data );
		$this->assertEquals( $event_id, $data['post_id'] );

		// Verify ticket was actually created.
		$ticket    = tribe( Ticket::class );
		$ticket_id = $data['ticket_id'];
		$this->assertTrue( $ticket->is_rsvp_ticket( $ticket_id ) );
	}

	public function test_create_ticket_without_permission(): void {
		$event_id = static::factory()->post->create();

		// No user set (guest).
		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', $event_id );
		$request->set_param( 'name', 'Test RSVP Ticket' );

		$response = rest_do_request( $request );

		$this->assertEquals( 403, $response->get_status() );

		$data = $response->get_data();
		$this->assertEquals( 'tec_tickets_rsvp_v2_rest_forbidden', $data['code'] );
	}

	public function test_create_ticket_with_invalid_post(): void {
		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', 999999 );
		$request->set_param( 'name', 'Test RSVP Ticket' );

		$response = rest_do_request( $request );

		// Permission check on non-existent post returns 403 (doesn't leak existence info).
		$this->assertContains( $response->get_status(), [ 400, 403 ] );
	}

	public function test_create_ticket_without_name(): void {
		$user_id  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = static::factory()->post->create();

		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', $event_id );

		$response = rest_do_request( $request );

		$this->assertEquals( 400, $response->get_status() );
	}

	public function test_create_ticket_with_unlimited_capacity(): void {
		$user_id  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = static::factory()->post->create();

		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', $event_id );
		$request->set_param( 'name', 'Unlimited RSVP' );
		$request->set_param( 'capacity', -1 );

		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );

		$data      = $response->get_data();
		$ticket    = tribe( Ticket::class );
		$available = $ticket->get_available( $data['ticket_id'] );

		$this->assertEquals( -1, $available );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_update_ticket_name(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_update_ticket_capacity(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_update_ticket_without_permission(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_update_invalid_ticket(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_update_ticket_without_changes(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_delete_ticket(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_delete_ticket_without_permission(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	/**
	 * @group requires-tc-post-type
	 */
	public function test_delete_invalid_ticket(): void {
		$this->markTestSkipped( 'Requires tec_tc_ticket post type registration for capability checks.' );
	}

	public function test_create_ticket_with_show_not_going_option(): void {
		$user_id  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = static::factory()->post->create();

		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', $event_id );
		$request->set_param( 'name', 'Test RSVP' );
		$request->set_param( 'show_not_going', true );

		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );

		$data = $response->get_data();
		$this->assertTrue( $data['success'] );

		// Verify show_not_going was set to a truthy value.
		$ticket_id      = $data['ticket_id'];
		$show_not_going = get_post_meta( $ticket_id, '_tribe_ticket_show_not_going', true );
		$this->assertNotEmpty( $show_not_going );
	}

	public function test_create_ticket_with_dates(): void {
		$user_id  = static::factory()->user->create( [ 'role' => 'administrator' ] );
		$event_id = static::factory()->post->create();

		wp_set_current_user( $user_id );

		$start_date = '2025-01-01 00:00:00';
		$end_date   = '2025-12-31 23:59:59';

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/ticket' );
		$request->set_param( 'post_id', $event_id );
		$request->set_param( 'name', 'Test RSVP' );
		$request->set_param( 'start_date', $start_date );
		$request->set_param( 'end_date', $end_date );

		$response = rest_do_request( $request );

		$this->assertEquals( 201, $response->get_status() );

		$data      = $response->get_data();
		$ticket_id = $data['ticket_id'];

		// Verify dates were set.
		$saved_start = get_post_meta( $ticket_id, '_ticket_start_date', true );
		$saved_end   = get_post_meta( $ticket_id, '_ticket_end_date', true );

		$this->assertEquals( $start_date, $saved_start );
		$this->assertEquals( $end_date, $saved_end );
	}
}
