<?php
namespace TEC\Tickets\RSVP\V2\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\RSVP\V2\Controller;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Traits\With_No_Query_Commit;
use WP_REST_Request;
use WP_REST_Server;

class Order_Endpoint_Test extends WPTestCase {
	use Ticket_Maker;

	// The Order class will call COMMIT during tests, with this we prevent it.
	use With_No_Query_Commit;

	/**
	 * @var WP_REST_Server|null
	 */
	private $rest_server_backup;

	public function setUp(): void {
		parent::setUp()	;
		$this->filter_query_to_avoid_commit_rollback();
	}

	public function tearDown():void{
		$this->remove_filter_query_to_avoid_commit_rollback();
		parent::tearDown();
	}

	/**
	 * @before
	 */
	public function backup_rest_server(): void {
		global $wp_rest_server;
		$this->rest_server_backup = $wp_rest_server instanceof WP_REST_Server ?
			clone $wp_rest_server
			: $wp_rest_server;
	}

	/**
	 * @after
	 */
	public function restore_rest_server(): void {
		global $wp_rest_server;
		$wp_rest_server = $this->rest_server_backup;
	}

	/**
	 * Ensure cart is clean before each test.
	 *
	 * @before
	 */
	public function ensure_clean_cart(): void {
		$cart = tribe( Cart::class );
		$cart->clear_cart();
	}

	/**
	 * Clean up cart after each test.
	 *
	 * @after
	 */
	public function cleanup_cart(): void {
		$cart = tribe( Cart::class );
		$cart->clear_cart();
	}

	private function register_endpoint(): void {
		tribe( Controller::class )->register();
		do_action( 'rest_api_init' );
	}

	/**
	 * @test
	 */
	public function it_should_return_response_for_missing_ticket_id(): void {
		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'step', 'success' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'html', $data );
	}

	/**
	 * @test
	 */
	public function it_should_return_error_for_invalid_ticket(): void {
		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', 99999 );
		$request->set_param( 'step', 'success' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertArrayHasKey( 'html', $data );
	}

	/**
	 * @test
	 */
	public function it_should_return_fields_step_html_for_valid_ticket(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'fields' );
		$request->set_param( 'going', 'yes' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertNotEmpty( $data['html'] );
	}

	/**
	 * @test
	 */
	public function it_should_process_success_step_and_create_order(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'success' );
		$request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => 1,
					'attendees' => [
						[
							'email'        => 'test@example.com',
							'full_name'    => 'Test User',
							'order_status' => 'yes',
							'optout'       => false,
						],
					],
				],
			]
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertNotEmpty( $data['html'] );
	}

	/**
	 * @test
	 */
	public function it_should_process_opt_in_step_with_valid_nonce(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$success_request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$success_request->set_param( 'ticket_id', $ticket_id );
		$success_request->set_param( 'step', 'success' );
		$success_request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => 1,
					'attendees' => [
						[
							'email'        => 'optin@example.com',
							'full_name'    => 'Opt In User',
							'order_status' => 'yes',
							'optout'       => false,
						],
					],
				],
			]
		);

		$success_response = rest_get_server()->dispatch( $success_request );
		$success_data     = $success_response->get_data();

		$this->assertTrue( $success_data['success'] );

		$attendee_ids = $success_data['html'];
		preg_match( '/data-attendee-ids="([^"]+)"/', $attendee_ids, $matches );
		$attendee_ids_flat = $matches[1] ?? '';

		preg_match( '/data-opt-in-nonce="([^"]+)"/', $success_data['html'], $nonce_matches );
		$opt_in_nonce = $nonce_matches[1] ?? '';

		$optin_request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$optin_request->set_param( 'ticket_id', $ticket_id );
		$optin_request->set_param( 'step', 'opt-in' );
		$optin_request->set_param( 'opt_in', true );
		$optin_request->set_param( 'attendee_ids', $attendee_ids_flat );
		$optin_request->set_param( 'opt_in_nonce', $opt_in_nonce );

		$optin_response = rest_get_server()->dispatch( $optin_request );
		$optin_data     = $optin_response->get_data();

		$this->assertEquals( 200, $optin_response->get_status() );
		$this->assertTrue( $optin_data['success'] );
	}

	/**
	 * @test
	 */
	public function it_should_reject_opt_in_step_with_invalid_nonce(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$success_request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$success_request->set_param( 'ticket_id', $ticket_id );
		$success_request->set_param( 'step', 'success' );
		$success_request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => 1,
					'attendees' => [
						[
							'email'        => 'invalid@example.com',
							'full_name'    => 'Invalid Nonce User',
							'order_status' => 'yes',
							'optout'       => false,
						],
					],
				],
			]
		);

		$success_response = rest_get_server()->dispatch( $success_request );
		$success_data     = $success_response->get_data();
		$this->assertTrue( $success_data['success'] );

		preg_match( '/data-attendee-ids="([^"]+)"/', $success_data['html'], $matches );
		$attendee_ids_flat = $matches[1] ?? '';

		$optin_request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$optin_request->set_param( 'ticket_id', $ticket_id );
		$optin_request->set_param( 'step', 'opt-in' );
		$optin_request->set_param( 'opt_in', true );
		$optin_request->set_param( 'attendee_ids', $attendee_ids_flat );
		$optin_request->set_param( 'opt_in_nonce', 'invalid_nonce_value' );

		$optin_response = rest_get_server()->dispatch( $optin_request );
		$optin_data     = $optin_response->get_data();

		if ( empty( $optin_data['html'] ) ) {
			$this->markTestSkipped( 'No \'v2/commerce/rsvp/messages/error\' template yet.' );
		}

		$this->assertEquals( 200, $optin_response->get_status() );
		$this->assertTrue( $optin_data['success'] );
		codecept_debug( $optin_data );
		$this->assertStringContainsString( 'verify', $optin_data['html'] );
	}

	/**
	 * @test
	 */
	public function it_should_parse_attendee_details_correctly(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'email'        => 'test@example.com',
					'full_name'    => 'Test User',
					'order_status' => 'yes',
					'optout'       => false,
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertIsArray( $result );
		$this->assertEquals( 'test@example.com', $result['email'] );
		$this->assertEquals( 'Test User', $result['full_name'] );
		$this->assertEquals( 'yes', $result['order_status'] );
		$this->assertFalse( $result['optout'] );
	}

	/**
	 * @test
	 */
	public function it_should_parse_attendee_details_from_tribe_tickets_param(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'tribe_tickets' => [
					123 => [
						'attendees' => [
							[
								'email'        => 'attendee@example.com',
								'full_name'    => 'Attendee Name',
								'order_status' => 'going',
								'optout'       => '1',
							],
						],
					],
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertIsArray( $result );
		$this->assertEquals( 'attendee@example.com', $result['email'] );
		$this->assertEquals( 'Attendee Name', $result['full_name'] );
		$this->assertEquals( 'yes', $result['order_status'] );
		$this->assertTrue( $result['optout'] );
	}

	/**
	 * @test
	 */
	public function it_should_return_false_for_missing_attendee_email(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'full_name'    => 'Test User',
					'order_status' => 'yes',
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function it_should_return_false_for_missing_attendee_name(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'email'        => 'test@example.com',
					'order_status' => 'yes',
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertFalse( $result );
	}

	/**
	 * @test
	 */
	public function it_should_normalize_going_order_status(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'email'        => 'test@example.com',
					'full_name'    => 'Test User',
					'order_status' => 'going',
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertEquals( 'yes', $result['order_status'] );
	}

	/**
	 * @test
	 */
	public function it_should_normalize_not_going_order_status(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'email'        => 'test@example.com',
					'full_name'    => 'Test User',
					'order_status' => 'not-going',
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertEquals( 'no', $result['order_status'] );
	}

	/**
	 * @test
	 */
	public function it_should_parse_ticket_quantity_from_tribe_tickets(): void {
		$endpoint  = tribe( Order_Endpoint::class );
		$ticket_id = 123;

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'tribe_tickets' => [
					$ticket_id => [
						'quantity' => 5,
					],
				],
			]
		);

		$result = $endpoint->parse_ticket_quantity( $ticket_id, $request );

		$this->assertEquals( 5, $result );
	}

	/**
	 * @test
	 */
	public function it_should_parse_ticket_quantity_from_quantity_param(): void {
		$endpoint  = tribe( Order_Endpoint::class );
		$ticket_id = 456;

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				"quantity_$ticket_id" => 3,
			]
		);

		$result = $endpoint->parse_ticket_quantity( $ticket_id, $request );

		$this->assertEquals( 3, $result );
	}

	/**
	 * @test
	 */
	public function it_should_return_zero_for_missing_quantity(): void {
		$endpoint  = tribe( Order_Endpoint::class );
		$ticket_id = 789;

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );

		$result = $endpoint->parse_ticket_quantity( $ticket_id, $request );

		$this->assertEquals( 0, $result );
	}

	/**
	 * @test
	 */
	public function it_should_handle_not_going_rsvp_response(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'success' );
		$request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => 1,
					'attendees' => [
						[
							'email'        => 'notgoing@example.com',
							'full_name'    => 'Not Going User',
							'order_status' => 'no',
							'optout'       => true,
						],
					],
				],
			]
		);

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
	}

	/**
	 * @test
	 */
	public function it_should_return_html_for_private_post_when_user_can_read(): void {
		$user_id = static::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'private' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'fields' );
		$request->set_param( 'going', 'yes' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertTrue( $data['success'] );
		$this->assertNotEmpty( $data['html'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_html_for_private_post_when_user_cannot_read(): void {
		wp_set_current_user( 0 );

		$post_id   = static::factory()->post->create( [ 'post_status' => 'private' ] );
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'fields' );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );
		$this->assertStringContainsString( 'Something happened here.', $data['html'] );
	}
}
