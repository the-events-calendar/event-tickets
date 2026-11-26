<?php
namespace TEC\Tickets\RSVP\V2\REST;

use Codeception\TestCase\WPTestCase;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Status\Completed;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Commerce\Ticket as TC_Ticket;
use TEC\Tickets\RSVP\V2\Controller;
use TEC\Tickets\Tests\Commerce\RSVP\V2\Ticket_Maker;
use Tribe\Tests\Traits\With_Uopz;
use Traits\With_No_Query_Commit;
use Tribe__Tickets__Tickets as Tickets;
use WP_Query;
use WP_REST_Request;
use WP_REST_Server;

class Order_Endpoint_Test extends WPTestCase {
	use Ticket_Maker;
	use With_Uopz;

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
	public function it_should_hide_attendees_list_in_confirmation_when_no_addon_enables_it(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		remove_all_filters( 'tec_tickets_rsvp_show_attendees_list' );

		$data = $this->submit_rsvp( $ticket_id );

		$this->assertTrue( $data['success'] );
		$this->assertStringNotContainsString( 'tec-tickets__attendees-list-item', $data['html'] );
	}

	/**
	 * @test
	 * @see https://linear.app/nexcess/issue/SOFT-3632 When shown, each confirmation row names its guest.
	 */
	public function it_should_render_attendee_names_in_confirmation_when_list_is_enabled(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		// Simulate an add-on (e.g. Event Tickets Plus) enabling the attendees list.
		add_filter( 'tec_tickets_rsvp_show_attendees_list', '__return_true', 100 );

		$data = $this->submit_rsvp( $ticket_id );

		$this->assertTrue( $data['success'] );
		$this->assertStringContainsString( 'tec-tickets__attendees-list-item-attendee-details-name', $data['html'], 'The confirmation rows should render each guest name.' );
		$this->assertGreaterThanOrEqual( 2, substr_count( (string) $data['html'], 'Guest Alpha' ), 'Each captured guest row should be named.' );

		remove_filter( 'tec_tickets_rsvp_show_attendees_list', '__return_true', 100 );
	}

	/**
	 * Submits a two-guest Going RSVP through the order endpoint.
	 *
	 * @param int $ticket_id The TC-RSVP ticket ID.
	 *
	 * @return array The endpoint response data.
	 */
	private function submit_rsvp( int $ticket_id ): array {
		$request = $this->rsvp_request( $ticket_id );

		$response = rest_get_server()->dispatch( $request );
		$data     = $response->get_data();

		$this->assertEquals( 200, $response->get_status() );

		return $data;
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
	 * An email containing HTML/script markup must be rejected outright rather than
	 * sanitized into a superficially-valid address.
	 *
	 * @test
	 */
	public function it_should_return_false_for_email_with_markup(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'email'        => '<script>alert(\'hello\');</script>@test.com',
					'full_name'    => 'Test User',
					'order_status' => 'yes',
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertFalse( $result );
	}

	/**
	 * Markup in the full name is stripped and accepted, unlike markup in the email
	 * which rejects the whole attendee. This asymmetry is intentional.
	 *
	 * @test
	 */
	public function it_should_strip_markup_from_full_name(): void {
		$endpoint = tribe( Order_Endpoint::class );

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_body_params(
			[
				'attendee' => [
					'email'        => 'test@example.com',
					'full_name'    => 'Little<script>alert(\'hello\');</script>Test',
					'order_status' => 'yes',
				],
			]
		);

		$result = $endpoint->parse_attendee_details( $request );

		$this->assertIsArray( $result );
		$this->assertEquals( 'LittleTest', $result['full_name'] );
		$this->assertEquals( 'test@example.com', $result['email'] );
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

	/**
	 * @test
	 */
	public function it_should_return_error_html_when_purchaser_data_is_invalid(): void {
		// Ensure no user is logged in so get_purchaser_data() validates the input.
		wp_set_current_user( 0 );

		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'success' );
		/*
		 * Provide invalid email that will cause parse_attendee_details to return false,
		 * which then causes get_purchaser_data to receive null values and return WP_Error.
		 */
		$request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => 1,
					'attendees' => [
						[
							'email'        => 'not-a-valid-email',
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
		// The error should be rendered as HTML, not cause a fatal error.
		$this->assertNotEmpty( $data['html'] );
	}

	/**
	 * @test
	 *
	 * Note: Attendees are not generated during the REST call because
	 * With_No_Query_Commit prevents modify_status from completing the
	 * status transitions that trigger Generate_Attendees. Instead, we
	 * capture the process result to verify the endpoint correctly
	 * determines the RSVP going status and creates the order.
	 */
	public function it_should_save_rsvp_status_meta_on_attendees_during_order_creation(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$captured_result = null;
		add_filter(
			'tec_tickets_rsvp_v2_render_step_template_args',
			static function ( $args ) use ( &$captured_result ) {
				$captured_result = $args['process_result'] ?? null;
				return $args;
			}
		);

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'success' );
		$request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => 2,
					'attendees' => [
						[
							'email'        => 'going@example.com',
							'full_name'    => 'Going User',
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
		$this->assertNotNull( $captured_result, 'Process result should be captured.' );
		$this->assertTrue( $captured_result['success'], 'Order creation should succeed.' );
		$this->assertArrayHasKey( 'opt_in_args', $captured_result, 'Process result should contain opt_in_args.' );
		$this->assertTrue( $captured_result['opt_in_args']['is_going'], 'is_going should be true for "yes" order status.' );
		$this->assertArrayHasKey( 'id', $captured_result, 'Process result should contain the order ID.' );
	}

	/**
	 * @test
	 *
	 * @see it_should_save_rsvp_status_meta_on_attendees_during_order_creation
	 */
	public function it_should_save_not_going_rsvp_status_meta_on_attendees_during_order_creation(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 100 ] ] );

		$this->register_endpoint();

		$captured_result = null;
		add_filter(
			'tec_tickets_rsvp_v2_render_step_template_args',
			static function ( $args ) use ( &$captured_result ) {
				$captured_result = $args['process_result'] ?? null;
				return $args;
			}
		);

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
		$this->assertNotNull( $captured_result, 'Process result should be captured.' );
		$this->assertTrue( $captured_result['success'], 'Order creation should succeed.' );
		$this->assertArrayHasKey( 'opt_in_args', $captured_result, 'Process result should contain opt_in_args.' );
		$this->assertFalse( $captured_result['opt_in_args']['is_going'], 'is_going should be false for "no" order status.' );
		$this->assertArrayHasKey( 'id', $captured_result, 'Process result should contain the order ID.' );
	}

	/**
	 * @test
	 */
	public function it_should_use_provided_name_when_logged_in_user_has_no_first_or_last_name(): void {
		// Create a user with no first/last name.
		$user_id = static::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_email' => 'nameless@example.com',
				'first_name' => '',
				'last_name'  => '',
			]
		);
		wp_set_current_user( $user_id );

		$purchaser_data = tribe( Order::class )->get_purchaser_data(
			[
				'purchaser' => [
					'name'  => 'RSVP Guest Name',
					'email' => 'nameless@example.com',
				],
			]
		);

		$this->assertIsArray( $purchaser_data );
		$this->assertSame( 'RSVP Guest Name', $purchaser_data['purchaser_full_name'], 'Should fall back to provided name when user has no first/last name.' );
		$this->assertSame( 'RSVP Guest Name', $purchaser_data['purchaser_first_name'], 'Should fall back to provided name for first name.' );
		$this->assertSame( $user_id, $purchaser_data['purchaser_user_id'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function it_should_use_user_name_when_logged_in_user_has_first_and_last_name(): void {
		$user_id = static::factory()->user->create(
			[
				'role'       => 'subscriber',
				'user_email' => 'named@example.com',
				'first_name' => 'John',
				'last_name'  => 'Doe',
			]
		);
		wp_set_current_user( $user_id );

		$purchaser_data = tribe( Order::class )->get_purchaser_data(
			[
				'purchaser' => [
					'name'  => 'Ignored Name',
					'email' => 'named@example.com',
				],
			]
		);

		$this->assertIsArray( $purchaser_data );
		$this->assertSame( 'John Doe', $purchaser_data['purchaser_full_name'], 'Should use user first/last name, not provided name.' );
		$this->assertSame( 'John', $purchaser_data['purchaser_first_name'] );

		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 * @see https://linear.app/nexcess/issue/SVUL-138
	 */
	public function it_should_not_create_an_order_for_a_paid_ticket(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_ticket( $post_id, 25, [ 'tribe-ticket' => [ 'capacity' => 10 ] ] );

		$stock_before  = get_post_meta( $ticket_id, '_stock', true );
		$orders_before = $this->tc_order_count();

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
							'email'        => 'attacker@example.test',
							'full_name'    => 'Paid Attacker',
							'order_status' => 'yes',
							'optout'       => false,
						],
					],
				],
			]
		);

		$response = rest_get_server()->dispatch( $request );

		$this->assertEquals( 200, $response->get_status() );
		$this->assertSame(
			$stock_before,
			get_post_meta( $ticket_id, '_stock', true ),
			'A paid ticket must not lose stock through the free RSVP order endpoint.'
		);
		$this->assertSame(
			$orders_before,
			$this->tc_order_count(),
			'No order should be created when a paid ticket is sent to the RSVP order endpoint.'
		);
	}

	/**
	 * @test
	 */
	public function it_should_accept_an_rsvp_at_the_per_order_maximum(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		add_filter( 'tribe_tickets_get_ticket_default_max_purchase', static fn() => 2 );
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 2 );

		$this->assertTrue( $data['success'] );
		$this->assertStringNotContainsString( 'does not have that many available', $data['html'] );
		$this->assertSame( $orders_before + 1, $this->tc_order_count() );
		$this->assertEquals( 3, Tickets::load_ticket_object( $ticket_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_an_rsvp_above_the_per_order_maximum(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		add_filter( 'tribe_tickets_get_ticket_default_max_purchase', static fn() => 2 );
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 3 );

		$this->assertStringContainsString( 'does not have that many available', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
		$this->assertEquals( 5, Tickets::load_ticket_object( $ticket_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_an_rsvp_above_the_remaining_stock(): void {
		$post_id       = static::factory()->post->create();
		$ticket_id     = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 3 ] ] );
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 10 );

		$this->assertStringContainsString( 'does not have that many available', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
		$this->assertEquals( 3, Tickets::load_ticket_object( $ticket_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_an_rsvp_after_the_sale_window_closed(): void {
		$post_id       = static::factory()->post->create();
		$ticket_id     = $this->create_tc_rsvp_ticket(
			$post_id,
			[
				'tribe-ticket'      => [ 'capacity' => 5 ],
				'ticket_start_date' => '1994-01-01',
				'ticket_start_time' => '08:00:00',
				'ticket_end_date'   => '1994-03-01',
				'ticket_end_time'   => '20:00:00',
			]
		);
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 1 );

		$this->assertStringContainsString( 'does not have that many available', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
		$this->assertEquals( 5, Tickets::load_ticket_object( $ticket_id )->stock() );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_an_rsvp_above_the_maximum_sent_as_a_quantity_field(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		add_filter( 'tribe_tickets_get_ticket_default_max_purchase', static fn() => 2 );
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();

		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'success' );
		$request->set_param( "quantity_{$ticket_id}", 3 );
		$request->set_param(
			'attendee',
			[
				'full_name'    => 'Field Guest',
				'email'        => 'field-guest@example.test',
				'order_status' => 'yes',
			]
		);

		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertStringContainsString( 'does not have that many available', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
	}

	/**
	 * @test
	 */
	public function it_should_refuse_an_rsvp_without_a_quantity(): void {
		$post_id       = static::factory()->post->create();
		$ticket_id     = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 0 );

		$this->assertStringContainsString( 'Please choose at least one guest.', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
	}

	/**
	 * Order statuses the order system may refuse while the endpoint confirms an RSVP.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function refused_status_provider(): array {
		return [
			'Pending refused'   => [ Pending::SLUG ],
			'Completed refused' => [ Completed::SLUG ],
		];
	}

	/**
	 * @test
	 * @dataProvider refused_status_provider
	 */
	public function it_should_discard_an_rsvp_whose_status_change_was_refused( string $refused_status ): void {
		$post_id       = static::factory()->post->create();
		$ticket_id     = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		$orders_before = $this->tc_order_count();
		$cookie        = Cart::get_cart_hash_cookie_name();

		$_COOKIE[ $cookie ] = 'visitor-cart-hash';

		/* Refuse only the given status, so each case reaches and fails on its own status change. */
		$this->set_class_fn_return(
			Order::class,
			'modify_status',
			static fn( $order_id, $new_status ) => $refused_status !== $new_status,
			true
		);

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 1 );

		$this->assertStringContainsString( 'Sorry, this RSVP is no longer available.', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
		$this->assertArrayNotHasKey( $cookie, $_COOKIE );
	}

	/**
	 * @test
	 */
	public function it_should_not_restore_stock_it_never_took_when_pending_is_refused(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );

		/* Simulate a racing request taking the places after the capacity check passed. */
		add_filter(
			'tec_tickets_rsvp_v2_cart_upsert_item_args',
			static function ( $args ) use ( $ticket_id ) {
				update_post_meta( $ticket_id, TC_Ticket::$stock_meta_key, 0 );

				return $args;
			}
		);

		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$data = $this->submit_rsvp_quantity( $ticket_id, 2 );

		$this->assertStringContainsString( 'Sorry, this RSVP is no longer available.', $data['html'] );
		$this->assertSame( $orders_before, $this->tc_order_count() );
		$this->assertEquals( 0, Tickets::load_ticket_object( $ticket_id )->stock() );
	}

	/**
	 * Here to implement the required abstract method, this method is a no-op since it will not be invoked.
	 */
	protected function avoid_query_commit_rollback_handler( string $query ): string {
		// No-op.
		return $query;
	}

	/**
	 * @test
	 */
	public function it_should_refuse_a_guest_rsvp_when_rsvp_requires_login(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		$this->require_rsvp_login( true );
		wp_set_current_user( 0 );
		$orders_before = $this->tc_order_count();

		$this->register_endpoint();
		$response = rest_get_server()->dispatch( $this->rsvp_request( $ticket_id ) );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'tec_tickets_rsvp_login_required', $response->as_error()->get_error_code() );
		$this->assertSame( $orders_before, $this->tc_order_count() );
	}

	/**
	 * @test
	 */
	public function it_should_accept_a_logged_in_rsvp_when_rsvp_requires_login(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		$this->require_rsvp_login( true );
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$this->register_endpoint();
		$data = $this->submit_rsvp( $ticket_id );

		$this->assertTrue( $data['success'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_ask_a_guest_to_log_in_to_rsvp_when_only_purchases_require_login(): void {
		$post_id   = static::factory()->post->create();
		$ticket_id = $this->create_tc_rsvp_ticket( $post_id, [ 'tribe-ticket' => [ 'capacity' => 5 ] ] );
		add_filter( 'tribe_get_option_ticket-authentication-requirements', static fn() => [ 'event-tickets_all' ] );
		wp_set_current_user( 0 );

		$this->register_endpoint();
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'going' );
		$data = rest_get_server()->dispatch( $request )->get_data();

		$this->assertStringNotContainsString( 'must be logged in', $data['html'] );
	}

	/**
	 * Turns the "Require users to log in before they RSVP" setting on or off for this test.
	 *
	 * A filter rather than the option, because the settings cache outlives the test's database rollback.
	 *
	 * @param bool $required Whether RSVP requires login.
	 */
	private function require_rsvp_login( bool $required ): void {
		add_filter(
			'tribe_get_option_ticket-authentication-requirements',
			static fn() => $required ? [ 'event-tickets_rsvp' ] : []
		);
	}

	/**
	 * Builds a Going RSVP request.
	 *
	 * @param int $ticket_id The TC-RSVP ticket ID.
	 * @param int $quantity  The quantity requested.
	 *
	 * @return WP_REST_Request The request.
	 */
	private function rsvp_request( int $ticket_id, int $quantity = 2 ): WP_REST_Request {
		$request = new WP_REST_Request( 'POST', '/tribe/tickets/v1/rsvp/v2/order' );
		$request->set_param( 'ticket_id', $ticket_id );
		$request->set_param( 'step', 'success' );
		$request->set_param(
			'tribe_tickets',
			[
				$ticket_id => [
					'quantity'  => $quantity,
					'attendees' => [
						[
							'email'        => 'guest@example.com',
							'full_name'    => 'Guest Alpha',
							'order_status' => 'going',
							'optout'       => false,
						],
					],
				],
			]
		);

		return $request;
	}

	/**
	 * Submits a Going RSVP for the given quantity through the order endpoint.
	 *
	 * @param int $ticket_id The TC-RSVP ticket ID.
	 * @param int $quantity  The quantity requested.
	 *
	 * @return array The endpoint response data.
	 */
	private function submit_rsvp_quantity( int $ticket_id, int $quantity ): array {
		$response = rest_get_server()->dispatch( $this->rsvp_request( $ticket_id, $quantity ) );

		$this->assertEquals( 200, $response->get_status() );

		return $response->get_data();
	}

	/**
	 * Counts the Tickets Commerce orders in any status.
	 *
	 * @return int The number of orders.
	 */
	private function tc_order_count(): int {
		$query = new WP_Query(
			[
				'post_type'      => Order::POSTTYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => false,
			]
		);

		return $query->found_posts;
	}
}
