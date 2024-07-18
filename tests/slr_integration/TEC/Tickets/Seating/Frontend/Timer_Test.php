<?php

namespace TEC\Tickets\Seating\Frontend;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Data_API as Data_API;

class Timer_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use WP_Remote_Mocks;
	use OAuth_Token;
	use Ticket_Maker;
	use Reservations_Maker;

	protected string $controller_class = Timer::class;

	/**
	 * @before
	 */
	public function ensure_tickets_commerce_active(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}

	/**
	 * @before
	 */
	public function ensure_posts_are_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	/**
	 * @before
	 * @after
	 */
	public function reset_cookie(): void {
		unset( $_COOKIE[ Session::COOKIE_NAME ] );
	}

	public function test_render_with_args(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );

		$this->make_controller()->register();

		$session = tribe( Session::class );
		$session->add_entry( $post_id, 'test-token' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', $post_id, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		$token   = 'test-token';

		ob_start();
		do_action( 'tec_tickets_seating_seat_selection_timer', $token, $post_id );
		$html = ob_get_clean();

		$html = str_replace(
			[ $post_id, $token ],
			[ '{{post_id}}', '{{token}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_render_from_cookie_data(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );

		$this->make_controller()->register();

		$session = tribe( Session::class );
		$session->add_entry( $post_id, 'test-token' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', $post_id, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		ob_start();
		do_action( 'tec_tickets_seating_seat_selection_timer' );
		$html = ob_get_clean();

		$html = str_replace( $post_id, '{{post_id}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_render_from_cookie_data_with_no_cookie_data(): void {
		$this->make_controller()->register();

		ob_start();
		do_action( 'tec_tickets_seating_seat_selection_timer' );
		$html = ob_get_clean();

		$this->assertEmpty( $html );
	}

	public function test_render_to_sync_with_no_info(): void {
		ob_start();
		$this->make_controller()->render_to_sync();
		$html = ob_get_clean();

		$this->assertEmpty( $html );
	}

	public function test_render_to_sync(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );

		$session = tribe( Session::class );
		$session->add_entry( $post_id, 'test-token' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', $post_id, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		$token = 'test-token';

		ob_start();
		$controller = $this->make_controller();
		$controller->render_to_sync();
		$html = ob_get_clean();

		$html = str_replace(
			[ $post_id, $token ],
			[ '{{post_id}}', '{{token}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_render_to_sync_with_previous_render(): void {
		$post_id = static::factory()->post->create();
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		$session  = tribe( Session::class );
		$sessions = tribe( Sessions::class );

		// Mock a previous session where the token and post ID were stored.
		$session->add_entry( $post_id, 'previous-token' );
		$sessions->upsert( 'previous-token', $post_id, time() + 100 );

		$controller = $this->make_controller();

		// Now render the timer a first time with a new token for the same post ID.
		ob_start();
		$controller->render( 'new-token', $post_id );
		ob_end_clean();

		// Now render to sync in the context of the same request.
		ob_start();
		$controller->render_to_sync();
		$sync_html = ob_get_clean();

		$sync_html = str_replace( $post_id, '{{post_id}}', $sync_html );

		$this->assertMatchesHtmlSnapshot( $sync_html );
	}

	public function test_get_localized_data(): void {
		$this->set_fn_return( 'wp_create_nonce', '22848eb6a0' );
		$this->assertMatchesJsonSnapshot(
			json_encode(
				$this->make_controller()->get_localized_data(),
				JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT | JSON_PRETTY_PRINT
			)
		);
	}

	public function test_ajax_check_request(): void {
		// Mock the wp_send_json_error response.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );

		$timer = $this->make_controller();

		// Start by not sending an AJAX nonce.
		unset( $_REQUEST['_ajax_nonce'] );

		$timer->ajax_start();

		$this->assertEquals( [
			'error' => 'Nonce verification failed',
		], $wp_send_json_error_data );
		$this->assertEquals( 403, $wp_send_json_error_code );

		// Send a wrong nonce.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'wrong_nonce' );

		$timer->ajax_start();

		$this->assertEquals( [
			'error' => 'Nonce verification failed',
		], $wp_send_json_error_data );
		$this->assertEquals( 403, $wp_send_json_error_code );

		// Send a correct nonce for another user.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'another_user' );
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$timer->ajax_start();

		$this->assertEquals( [
			'error' => 'Nonce verification failed',
		], $wp_send_json_error_data );
		$this->assertEquals( 403, $wp_send_json_error_code );

		// Send correct nonce, but no token.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		unset( $_REQUEST['token'], $_REQUEST['postId'] );

		$timer->ajax_start();

		$this->assertEquals( [
			'error' => 'Missing required parameters',
		], $wp_send_json_error_data );
		$this->assertEquals( 400, $wp_send_json_error_code );

		// Send correct nonce and token, but no post ID.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		unset( $_REQUEST['postId'] );

		$timer->ajax_start();

		$this->assertEquals( [
			'error' => 'Missing required parameters',
		], $wp_send_json_error_data );
		$this->assertEquals( 400, $wp_send_json_error_code );
	}

	public function test_ajax_start(): void {
		// Create a previous session.
		$session      = tribe( Session::class );
		$sessions     = tribe( Sessions::class );
		$reservations = tribe( Reservations::class );
		$session->add_entry( 23, 'test-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', $this->create_mock_reservations_data( [ 23 ], 2 ) );

		// Set up the request context.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = 23;
		$this->set_oauth_token( 'auth-token' );

		// Previous session reservations should be cancelled on the service.
		$service_cancellations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			function () use ( &$service_cancellations ) {
				$service_cancellations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ 'reservation-id-1', 'reservation-id-2' ],
						]
					),
				];
			},
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode(
					[
						'success' => true,
					]
				),
			]
		);

		// Mock the wp_send_json_success response.
		$wp_send_json_success_data = null;
		$wp_send_json_success_code = null;
		$this->set_fn_return( 'wp_send_json_success',
			function ( $data, $code = 200 ) use ( &$wp_send_json_success_data, &$wp_send_json_success_code ) {
				$wp_send_json_success_data = $data;
				$wp_send_json_success_code = $code;
			},
			true );

		$timer = $this->make_controller();
		$timer->register();

		do_action( 'wp_ajax_nopriv_' . Timer::ACTION_START );

		$this->assertEquals( 1, $service_cancellations );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->assertEquals( [ 23 => 'test-token' ], $session->get_entries() );
		$this->assertEquals( 200, $wp_send_json_success_code );
		$timeout = $timer->get_timeout( 23 );
		$this->assertEquals( $timeout, $wp_send_json_success_data['secondsLeft'] );
		$this->assertEqualsWithDelta( time(), (int) $wp_send_json_success_data['timestamp'], 5 );
	}

	public function test_ajax_start_fails_if_session_upsert_fails(): void {
		// Set up the request context.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = 23;
		$this->set_oauth_token( 'auth-token' );

		// Mock the wp_send_json_error response.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code = 200 ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );
		// Mock the Sessions table dependency of the service to return `false` on the `upsert` method.
		$this->test_services->singleton( Sessions::class, $this->make( Sessions::class, [
			'upsert' => false
		] ) );

		$timer = $this->make_controller();
		$timer->register();

		do_action( 'wp_ajax_nopriv_' . Timer::ACTION_START );

		$this->assertEquals( 500, $wp_send_json_error_code );
		$this->assertEquals( [
			'error' => 'Failed to start timer',
		], $wp_send_json_error_data );
	}

	public function test_ajax_sync(): void {
		// Create a previous session.
		$session      = tribe( Session::class );
		$sessions     = tribe( Sessions::class );
		$reservations = tribe( Reservations::class );
		$session->add_entry( 23, 'test-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		// Set up the request context.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = 23;
		$this->set_oauth_token( 'auth-token' );

		// Mock the wp_send_json_success response.
		$wp_send_json_success_data = null;
		$wp_send_json_success_code = null;
		$this->set_fn_return( 'wp_send_json_success',
			function ( $data, $code = 200 ) use ( &$wp_send_json_success_data, &$wp_send_json_success_code ) {
				$wp_send_json_success_data = $data;
				$wp_send_json_success_code = $code;
			},
			true );

		$timer = $this->make_controller();
		$timer->register();

		do_action( 'wp_ajax_nopriv_' . Timer::ACTION_SYNC );

		$this->assertEquals( 200, $wp_send_json_success_code );
		$this->assertEqualsWithDelta( 100, $wp_send_json_success_data['secondsLeft'], 5 );
		$this->assertEqualsWithDelta( time(), (int) $wp_send_json_success_data['timestamp'], 5 );
	}

	public function interrupt_data_provider(): \Generator {
		yield 'post with no tickets available' => [
			function () {
				$post_id = static::factory()->post->create();
				update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );

				return $post_id;
			}
		];

		yield 'post with tickets available' => [
			function () {
				$post_id = static::factory()->post->create();
				update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
				$ticket_id = $this->create_tc_ticket( $post_id, 10 );

				return $post_id;
			}
		];

		yield 'event with no tickets available' => [
			function () {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '+1 week 10 am',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				update_post_meta( $event_id, Meta::META_KEY_UUID, 'test-event-uuid' );

				return $event_id;
			}
		];

		yield 'event with tickets available' => [
			function () {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '+1 week 10 am',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;
				update_post_meta( $event_id, Meta::META_KEY_UUID, 'test-event-uuid' );
				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				return $event_id;
			}
		];
	}

	/**
	 * @dataProvider interrupt_data_provider
	 */
	public function test_ajax_interrupt( \Closure $fixture ): void {
		$post_id = $fixture();

		// Create a previous session.
		$session      = tribe( Session::class );
		$sessions     = tribe( Sessions::class );
		$reservations = tribe( Reservations::class );
		$session->add_entry( $post_id, 'test-token' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions->upsert( 'test-token', $post_id, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		// Set up the request context.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = $post_id;
		$this->set_oauth_token( 'auth-token' );

		// Previous session reservations should be cancelled on the service.
		$service_cancellations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			function () use ( &$service_cancellations ) {
				$service_cancellations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode(
					[
						'success' => true,
					]
				),
			]
		);

		// Mock the wp_send_json_success response.
		$wp_send_json_success_data = null;
		$wp_send_json_success_code = null;
		$this->set_fn_return( 'wp_send_json_success',
			function ( $data, $code = 200 ) use ( &$wp_send_json_success_data, &$wp_send_json_success_code ) {
				$wp_send_json_success_data = $data;
				$wp_send_json_success_code = $code;
			},
			true );

		$timer = $this->make_controller();
		$timer->register();

		do_action( 'wp_ajax_nopriv_' . Timer::ACTION_INTERRUPT_GET_DATA );

		$this->assertEquals( 1, $service_cancellations );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->assertEquals( [], $session->get_entries() );
		$this->assertNull(
			DB::get_row(
				DB::prepare(
					"SELECT * FROM %i WHERE token = %s",
					Sessions::table_name(),
					'test-token'
				)
			),
			'On interruption, the token session should have been removed from the database.'
		);
		$this->assertEquals( 200, $wp_send_json_success_code );
		$this->assertMatchesJsonSnapshot(
			str_replace(
				$post_id,
				'{{post_id}}',
				wp_json_encode(
					$wp_send_json_success_data,
					JSON_UNESCAPED_SLASHES | JSON_HEX_QUOT | JSON_PRETTY_PRINT
				)
			)
		);
	}

	public function test_ajax_interrupt_fails_if_reservation_cancellation_fails(): void {
		$post_id = static::factory()->post->create();
		// Create a previous session.
		$session      = tribe( Session::class );
		$sessions     = tribe( Sessions::class );
		$reservations = tribe( Reservations::class );
		$session->add_entry( $post_id, 'test-token' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions->upsert( 'test-token', $post_id, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		// Set up the request context.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = $post_id;
		$this->set_oauth_token( 'auth-token' );

		// Previous session reservations cancellation will fail.
		$service_cancellations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			function () use ( &$service_cancellations ) {
				$service_cancellations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			[
				'response' => [
					'code' => 400,
				],
				'body'     => wp_json_encode(
					[
						'success' => false,
					]
				),
			]
		);

		// Mock the wp_send_json_error response.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code = 200 ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );

		$timer = $this->make_controller();
		$timer->register();

		do_action( 'wp_ajax_nopriv_' . Timer::ACTION_INTERRUPT_GET_DATA );

		$this->assertEquals( 1, $service_cancellations );
		$this->assertEquals( [ '1234567890', '0987654321' ], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->assertEquals( [], $session->get_entries() );
		$this->assertEquals( 500, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Failed to cancel the reservations' ], $wp_send_json_error_data );
	}

	public function test_ajax_interrupt_fails_if_token_session_deletion_fails(): void {
		$post_id = static::factory()->post->create();
		// Create a previous session.
		$session      = tribe( Session::class );
		$sessions     = tribe( Sessions::class );
		$reservations = tribe( Reservations::class );
		$session->add_entry( $post_id, 'test-token' );
		update_post_meta( $post_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions->upsert( 'test-token', $post_id, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );

		// Set up the request context.
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Session::COOKIE_NAME );
		$_REQUEST['token']       = 'test-token';
		$_REQUEST['postId']      = $post_id;
		$this->set_oauth_token( 'auth-token' );

		$service_cancellations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			function () use ( &$service_cancellations ) {
				$service_cancellations ++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [ '1234567890', '0987654321' ],
						]
					),
				];
			},
			[
				'response' => [
					'code' => 200,
				],
				'body'     => wp_json_encode(
					[
						'success' => true,
					]
				),
			]
		);

		// Mock the wp_send_json_error response.
		$wp_send_json_error_data = null;
		$wp_send_json_error_code = null;
		$this->set_fn_return( 'wp_send_json_error',
			function ( $data, $code = 200 ) use ( &$wp_send_json_error_data, &$wp_send_json_error_code ) {
				$wp_send_json_error_data = $data;
				$wp_send_json_error_code = $code;
			},
			true );

		// Mock the Sessions table dependency of the service to return `false` on the `clear_token_reservations` method.
		$this->test_services->singleton( Sessions::class, $this->make( Sessions::class, [
			'delete_token_session' => false
		] ) );

		$timer = $this->make_controller();
		$timer->register();

		do_action( 'wp_ajax_nopriv_' . Timer::ACTION_INTERRUPT_GET_DATA );

		$this->assertEquals( 1, $service_cancellations );
		$this->assertEquals( [ '1234567890', '0987654321' ], $sessions->get_reservations_for_token( 'test-token' ) );
		$this->assertEquals( [], $session->get_entries() );
		$this->assertEquals( 500, $wp_send_json_error_code );
		$this->assertEquals( [ 'error' => 'Failed to cancel the reservations' ], $wp_send_json_error_data );
	}

	public function test_will_not_render_if_post_not_ticketed(): void {
		$post_id = self::factory()->post->create();

		$controller = $this->make_controller();
		$controller->register();
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );

		ob_start();
		do_action( 'tec_tickets_seating_seat_selection_timer', 'test-token', $post_id );
		$html = ob_get_clean();

		$this->assertEmpty( $html );
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );

		ob_start();
		// Render to sync.
		$controller->render_to_sync();
		$render_to_sync_html = ob_get_clean();

		$this->assertEmpty( $render_to_sync_html );
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );
	}

	public function test_will_not_render_if_seating_not_enabled_on_post(): void {
		$post_id = self::factory()->post->create();
		$ticket  = $this->create_tc_ticket( $post_id, 10 );
		// Ensure Seat Selection is not enabled on the post.
		delete_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID );

		$controller = $this->make_controller();
		$controller->register();
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );

		ob_start();
		do_action( 'tec_tickets_seating_seat_selection_timer', 'test-token', $post_id );
		$html = ob_get_clean();

		$this->assertEmpty( $html );
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );

		// Render to sync.
		ob_start();
		$controller->render_to_sync();
		$render_to_sync_html = ob_get_clean();

		$this->assertEmpty( $render_to_sync_html );
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );
	}

	public function test_will_not_render_if_seating_not_enabled_on_post_and_has_session(): void {
		$post_id = self::factory()->post->create();
		$ticket  = $this->create_tc_ticket( $post_id, 10 );
		// Ensure Seat Selection is not enabled on the post.
		delete_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID );
		// Create a session that contains information for another post.
		$post_with_assigned_seating = self::factory()->post->create();
		update_post_meta( $post_with_assigned_seating, Meta::META_KEY_LAYOUT_ID, 'some-layout-id' );
		$session  = tribe( Session::class );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', $post_with_assigned_seating, time() + 100 );
		$sessions->update_reservations( 'test-token', [ '1234567890', '0987654321' ] );
		$session->add_entry( $post_with_assigned_seating, 'test-token' );

		$controller = $this->make_controller();
		$controller->register();
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );

		ob_start();
		do_action( 'tec_tickets_seating_seat_selection_timer', 'test-token', $post_id );
		$html = ob_get_clean();

		$this->assertEmpty( $html );
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );

		// Render to sync.
		ob_start();
		$controller->render_to_sync();
		$render_to_sync_html = ob_get_clean();

		$this->assertNotEmpty( $render_to_sync_html, 'Render to sync should render.' );
		$this->assertStringContainsString(
			'data-post-id="' . $post_with_assigned_seating . '"',
			$render_to_sync_html,
			'Render to sync should render for the correct post ID.'
		);
		$this->assertEmpty( $controller->get_current_token() );
		$this->assertEmpty( $controller->get_current_post_id() );
	}
}