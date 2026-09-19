<?php

namespace TEC\Tickets\RSVP\V2\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\RSVP\V2\Constants;
use TEC\Tickets\RSVP\V2\Controller;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use WP_REST_Request;
use WP_REST_Server;

class Ticket_Endpoint_Test extends WPTestCase {
	use Ticket_Maker;

	/**
	 * @var WP_REST_Server|null
	 */
	private $rest_server_backup;

	/**
	 * @before
	 */
	public function backup_rest_server(): void {
		global $wp_rest_server;
		$this->rest_server_backup = $wp_rest_server instanceof WP_REST_Server
			? clone $wp_rest_server
			: $wp_rest_server;
	}

	/**
	 * @after
	 */
	public function restore_rest_server(): void {
		global $wp_rest_server;
		$wp_rest_server = $this->rest_server_backup;
	}

	private function register_endpoint(): void {
		tribe( Controller::class )->register();
		do_action( 'rest_api_init' );
	}

	private function create_admin_user(): int {
		return static::factory()->user->create( [ 'role' => 'administrator' ] );
	}

	public function test_should_reject_request_without_nonce(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request without nonce should be rejected' );
	}

	public function test_should_reject_request_with_invalid_nonce(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( '_wpnonce', 'invalid_nonce' );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request with invalid nonce should be rejected' );
	}

	public function test_should_reject_request_from_non_admin_user(): void {
		$user_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 403, $response->get_status(), 'Request from non-admin should be rejected' );
	}

	public function test_should_create_rsvp_ticket(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'rsvp_limit', 50 );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertArrayHasKey( 'ticket_id', $data );
		$this->assertGreaterThan( 0, $data['ticket_id'] );

		// Verify ticket was created with correct type.
		$ticket_type = get_post_meta( $data['ticket_id'], '_type', true );
		$this->assertEquals( Constants::TC_RSVP_TYPE, $ticket_type );
	}

	public function test_should_create_rsvp_ticket_with_unlimited_capacity(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		// Empty rsvp_limit means unlimited.
		$request->set_param( 'rsvp_limit', '' );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
	}

	public function test_should_update_existing_rsvp_ticket(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'rsvp_id', $rsvp_ticket_id );
		$request->set_param( 'rsvp_limit', 100 );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $rsvp_ticket_id, $data['ticket_id'] );
	}

	public function test_should_delete_rsvp_ticket(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'DELETE', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'ticket_id', $rsvp_ticket_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $rsvp_ticket_id, $data['ticket_id'] );

		// Verify ticket was deleted.
		$ticket = get_post( $rsvp_ticket_id );
		$this->assertNull( $ticket );
	}

	public function test_should_return_error_for_delete_with_missing_post_id(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'DELETE', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'ticket_id', $rsvp_ticket_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
		$this->assertArrayHasKey( 'message', $data );
	}

	public function test_should_return_error_for_delete_with_missing_ticket_id(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'DELETE', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
	}

	public function test_should_return_error_for_delete_with_invalid_ticket(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'DELETE', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'ticket_id', 99999 );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertFalse( $data['success'] );
	}

	public function test_should_update_ticket_meta(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket/meta' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'ticket_id', $rsvp_ticket_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertEquals( $rsvp_ticket_id, $data['ticket_id'] );
	}

	public function test_should_return_error_for_meta_update_with_missing_post_id(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket/meta' );
		$request->set_param( 'ticket_id', $rsvp_ticket_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
	}

	public function test_should_return_error_for_meta_update_with_missing_ticket_id(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket/meta' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 400, $response->get_status() );
		$this->assertFalse( $data['success'] );
	}

	public function test_should_return_error_for_meta_update_with_invalid_ticket(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket/meta' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'ticket_id', 99999 );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 404, $response->get_status() );
		$this->assertFalse( $data['success'] );
	}

	public function test_should_fire_after_save_action(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id      = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$action_fired = false;
		$action_args  = [];

		add_action(
			'tec_tickets_rsvp_after_save',
			function ( $rsvp_id, $post_id, $args, $request_params ) use ( &$action_fired, &$action_args ) {
				$action_fired = true;
				$action_args  = compact( 'rsvp_id', 'post_id', 'args', 'request_params' );
			},
			10,
			4
		);

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'rsvp_limit', 50 );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		rest_get_server()->dispatch( $request );

		$this->assertTrue( $action_fired, 'tec_tickets_rsvp_after_save action should be fired' );
		$this->assertGreaterThan( 0, $action_args['rsvp_id'] );
		$this->assertEquals( $post_id, $action_args['post_id'] );
	}

	public function test_should_fire_after_meta_update_action(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$action_fired = false;
		$action_args  = [];

		add_action(
			'tec_tickets_rsvp_after_meta_update',
			function ( $ticket_id, $post_id, $request_params ) use ( &$action_fired, &$action_args ) {
				$action_fired = true;
				$action_args  = compact( 'ticket_id', 'post_id', 'request_params' );
			},
			10,
			3
		);

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket/meta' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'ticket_id', $rsvp_ticket_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		rest_get_server()->dispatch( $request );

		$this->assertTrue( $action_fired, 'tec_tickets_rsvp_after_meta_update action should be fired' );
		$this->assertEquals( $rsvp_ticket_id, $action_args['ticket_id'] );
		$this->assertEquals( $post_id, $action_args['post_id'] );
	}

	public function test_should_fire_before_and_after_delete_actions(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id        = static::factory()->post->create( [ 'post_status' => 'publish' ] );
		$rsvp_ticket_id = $this->create_tc_rsvp_ticket( $post_id );

		$before_action_fired = false;
		$after_action_fired  = false;

		add_action(
			'tec_tickets_rsvp_before_delete',
			function () use ( &$before_action_fired ) {
				$before_action_fired = true;
			},
			10,
			3
		);

		add_action(
			'tec_tickets_rsvp_after_delete',
			function () use ( &$after_action_fired ) {
				$after_action_fired = true;
			},
			10,
			3
		);

		$this->register_endpoint();

		$request = new WP_REST_Request( 'DELETE', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( 'ticket_id', $rsvp_ticket_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		rest_get_server()->dispatch( $request );

		$this->assertTrue( $before_action_fired, 'tec_tickets_rsvp_before_delete action should be fired' );
		$this->assertTrue( $after_action_fired, 'tec_tickets_rsvp_after_delete action should be fired' );
	}

	public function test_should_accept_nonce_from_header(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_header( 'X-WP-Nonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}

	public function test_should_accept_nonce_from_param(): void {
		$user_id = $this->create_admin_user();
		wp_set_current_user( $user_id );

		$post_id = static::factory()->post->create( [ 'post_status' => 'publish' ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/ticket' );
		$request->set_param( 'post_ID', $post_id );
		$request->set_param( '_wpnonce', wp_create_nonce( 'wp_rest' ) );

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
	}
}
