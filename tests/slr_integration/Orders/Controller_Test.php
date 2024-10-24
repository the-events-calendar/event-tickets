<?php

namespace TEC\Tickets\Seating\Orders;

use Closure;
use Generator;
use PHPUnit\Framework\AssertionFailedError;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Gateways\PayPal\Gateway;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Shortcodes\Success_Shortcode;
use TEC\Tickets\Commerce\Status\Pending;
use TEC\Tickets\Seating\Admin\Ajax;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Orders\Attendee as Orders_Attendee;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Reservations;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Shortcode\Manager;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tests\Traits\WP_Remote_Mocks;
use Tribe\Tests\Traits\WP_Send_Json_Mocks;
use Tribe\Tickets\Test\Commerce\Attendee_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Date_Utils;
use Tribe__Tickets__Attendees as Attendees;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Tickets_View as Tickets_View;
use Tribe\Tickets\Test\Commerce\RSVP\Ticket_Maker as RSVP_Ticket_Maker;
use Tribe__Tickets__Global_Stock as Global_Stock;
use TEC\Tickets\Seating\Tables\Seat_Types;
use TEC\Tickets\Seating\Tests\Integration\Truncates_Custom_Tables;

class Controller_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use With_Uopz;
	use Ticket_Maker;
	use Order_Maker;
	use With_Tickets_Commerce;
	use WP_Remote_Mocks;
	use OAuth_Token;
	use Reservations_Maker;
	use Attendee_Maker;
	use WP_Send_Json_Mocks;
	use RSVP_Ticket_Maker;
	use Truncates_Custom_Tables;

	protected string $controller_class = Controller::class;

	private function get_attendee_data( array $attendees ): array {
		return array_reduce(
			$attendees,
			function ( array $carry, array $attendee ): array {
				foreach (
					[
						'ID',
						'ticket_id',
						'purchaser_name',
						'purchaser_email',
						'ticket_name',
						'holder_name',
						'security_code',
					] as $key
				) {
					if ( ! isset( $attendee[ $key ] ) ) {
						continue;
					}

					$value = $attendee[ $key ];

					if ( empty( $value ) ) {
						continue;
					}

					$carry[ esc_html( $value ) ] = strtoupper( $key );
					$carry[ $value ]             = strtoupper( $key );
				}

				return $carry;
			},
			[]
		);
	}

	public function attendee_data_provider(): Generator {
		yield 'single event with 3 seated ticket attendee' => [
			function (): array {
				$cart = new Cart();
				$this->set_class_fn_return( Cart::class, 'get_mode', 'test' );
				$this->set_class_property( $cart, 'available_modes', [ 'redirect', 'test' ] );
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single seated attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 1 );

				$tribe_tickets_ar_data = wp_json_encode(
					[
						'tribe_tickets_tickets' => [
							[
								'ticket_id' => $ticket_id,
								'quantity'  => 3,
								'optout'    => '1',
								// 'seat_labels' => [ 'B-4', 'D-1', 'C-3' ],
							],
						],
						'tribe_tickets_meta'    => [],
						'tribe_tickets_post_id' => $event_id,
					]
				);

				$data = [
					'provider'                       => 'TEC\\Tickets\\Commerce\\Module',
					'attendee'                       => [
						'optout' => 1,
					],
					'tickets_tickets_ar'             => 1,
					'tribe_tickets_saving_attendees' => 1,
					'tribe_tickets_ar_data'          => $tribe_tickets_ar_data,
					'_wpnonce'                       => '1234567890',
					'tec-tc-cart'                    => 'test',
				];

				// Create a session cookie for the user on the event.
				$session = tribe( Session::class );
				$session->add_entry( $event_id, 'test-token' );
				// Create a session in the database for user on the event.
				$sessions = tribe( Sessions::class );
				$sessions->upsert( 'test-token', $event_id, time() + DAY_IN_SECONDS );
				$sessions->update_reservations(
					'test-token',
					$this->create_mock_reservations_data( [ $ticket_id ], 3 )
				);

				// Merge new data with existing $_POST data
				$_POST = array_merge( $_POST, $data );

				$cart->parse_request();

				$purchaser = [
					'purchaser_user_id'    => 0,
					'purchaser_full_name'  => 'Test Purchaser',
					'purchaser_first_name' => 'Test',
					'purchaser_last_name'  => 'Purchaser',
					'purchaser_email'      => 'test-' . uniqid() . '@test.com',
				];

				$orders = tribe( Order::class );
				$order  = $orders->create_from_cart( tribe( Gateway::class ), $purchaser );

				$orders->modify_status( $order->ID, Pending::SLUG );

				clean_post_cache( $order->ID );
				$cart->clear_cart();

				return [ $event_id, [ $event_id, $ticket_id ], $order->ID ];
			},
		];

		yield 'single event with 3 regular Ticket attendees' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 1 );

				$ticket_id = $this->create_tc_ticket( $event_id );
				$order     = $this->create_order( [ $ticket_id => 3 ] );

				return [ $event_id, [ $event_id, $ticket_id ], $order->ID ];
			},
		];
	}

	/**
	 * Test the attendee list seat column data.
	 *
	 * @dataProvider attendee_data_provider
	 *
	 * @return void
	 */
	public function test_attendee_list_seat_column( Closure $fixture ): void {
		$_GET['search'] = '';
		$_GET['page']   = 'tickets-attendees';

		$this->make_controller()->register();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		$this->set_fn_return( 'is_admin', 'true' );
		$this->set_fn_return( 'uniqid', 'xxxxxx' );
		// Disable showing random notice from Upsell::show_on_attendees_page.
		$this->set_fn_return( 'wp_rand', 0 );

		[ $post_id, $post_ids, $order_id ] = $fixture();

		$_GET['event_id'] = $post_id;

		tribe_cache()->reset();
		ob_start();

		$attendees = tribe( Attendees::class );
		$attendees->screen_setup();
		$attendees->render();
		$html = ob_get_clean();

		// Stabilize snapshots.
		$attendee_data = $this->get_attendee_data( $attendees->attendees_table->items );
		$replace       = array_combine( $post_ids, array_fill( 0, count( $post_ids ), 'POST_ID' ) ) + $attendee_data + [ $order_id => 'ORDER_ID' ];
		uksort(
			$replace,
			function ( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			}
		);
		$html = str_replace(
			[
				...array_keys( $replace ),
				// Ensure consistency of Common URLs.
				'wp-content/plugins/the-events-calendar/common',
			],
			[
				...array_values( $replace ),
				'wp-content/plugins/event-tickets/common',
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * Test the attendee list seat column data.
	 *
	 * @dataProvider attendee_data_provider
	 *
	 * @return void
	 */
	public function test_attendee_list_seat_column_desc_order( Closure $fixture ): void {
		$_GET['search']  = '';
		$_GET['page']    = 'tickets-attendees';
		$_GET['orderby'] = 'seat';
		$_GET['order']   = 'desc';

		$this->make_controller()->register();
		wp_set_current_user( static::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );

		[ $post_id, $post_ids, $order_id ] = $fixture();

		$_GET['event_id'] = $post_id;

		tribe_cache()->reset();
		ob_start();

		$attendees = tribe( Attendees::class );
		$attendees->screen_setup();
		$attendees->render();
		$html = ob_get_clean();

		// Stabilize snapshots.
		$attendee_data = $this->get_attendee_data( $attendees->attendees_table->items );
		$replace       = array_combine( $post_ids, array_fill( 0, count( $post_ids ), 'POST_ID' ) ) + $attendee_data + [ $order_id => 'ORDER_ID' ];
		uksort(
			$replace,
			function ( $a, $b ) {
				return strlen( $b ) <=> strlen( $a );
			}
		);
		$html = str_replace(
			[
				...array_keys( $replace ),
				// Ensure consistency of Common URLs.
				'wp-content/plugins/the-events-calendar/common',
			],
			[
				...array_values( $replace ),
				'wp-content/plugins/event-tickets/common',
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_confirm_all_reservations_on_attendee_creation(): void {
		$reservations = tribe( Reservations::class );
		// Listen for a call to the service to confirm the reservations..
		$service_confirmations = 0;
		$this->mock_wp_remote(
			'post',
			$reservations->get_confirm_url(),
			function () use ( &$service_confirmations ) {
				$service_confirmations++;

				return [
					'headers' => [
						'Authorization' => 'Bearer auth-token',
						'Content-Type'  => 'application/json',
					],
					'body'    => wp_json_encode(
						[
							'eventId' => 'test-post-uuid',
							'ids'     => [
								'reservation-id-1',
								'reservation-id-2',
								'reservation-id-3',
								'reservation-id-4',
							],
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
		$session = tribe( Session::class );
		$session->add_entry( 23, 'test-token' );
		update_post_meta( 23, Meta::META_KEY_UUID, 'test-post-uuid' );
		$sessions = tribe( Sessions::class );
		$sessions->upsert( 'test-token', 23, time() + 100 );
		$sessions->update_reservations( 'test-token', $this->create_mock_reservations_data( [ 23, 89 ], 2 ) );
		$this->set_oauth_token( 'auth-token' );

		$controller = $this->make_controller();
		$controller->register();

		$controller->confirm_all_reservations();

		$this->assertEquals( 1, $service_confirmations );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );

		// Calling it a second time in the context of the same request should not send a new request.
		// This will be called for each Attendee created, there might be many calls to the service in the same request.
		$controller->confirm_all_reservations();

		$this->assertEquals( 1, $service_confirmations );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );

		// Calling it a third time in the context of the same request should not send a new request.
		$controller->confirm_all_reservations();

		$this->assertEquals( 1, $service_confirmations );
		$this->assertEquals( [], $sessions->get_reservations_for_token( 'test-token' ) );
	}

	public function test_deleting_attendee_without_seats() {
		$controller = $this->make_controller();
		$controller->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with seated attendees',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 100, $ticket->available(), 'There should be 100 tickets available' );

		// create order.
		$order = $this->create_order( [ $ticket_a_id => 2 ] );

		//get attendees.
		$attendees = tribe_attendees()->where( 'event_id', $event_id )->all();

		$this->assertEquals( 2, count( $attendees ), 'There should be 2 attendees' );

		// delete attendee.
		$attendee = $attendees[0];
		$deleted  = tribe( Attendee::class )->delete( $attendee->ID );

		$new_count = tec_tc_attendees()->by( 'event_id', $event_id )->count();
		$this->assertEquals( 1, $new_count, 'There should be 1 attendee' );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 99, $ticket->available(), 'There should be 99 tickets available' );
	}

	public function test_deleting_attendee_with_seats_but_reservation_cancel_failed() {
		$controller = $this->make_controller();
		$controller->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with seated attendees',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// setup mock data for reservation api call.
		update_post_meta( $event_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$this->set_oauth_token( 'auth-token' );

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 100, $ticket->available(), 'There should be 100 tickets available' );

		// create order.
		$order = $this->create_order( [ $ticket_a_id => 2 ] );

		//get attendees.
		$attendees = tribe_attendees()->where( 'event_id', $event_id )->all();

		$this->assertEquals( 2, count( $attendees ), 'There should be 2 attendees' );

		$reservations = tribe( Reservations::class );

		$mock_reservation_cancel_failed = $this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'eventId' => 'test-post-uuid',
						'ids'     => [
							'seat-reservation-id',
						],
					]
				),
			],
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

		// delete attendee.
		$attendee = $attendees[0];

		update_post_meta( $attendee->ID, Meta::META_KEY_RESERVATION_ID, 'seat-reservation-id' );
		update_post_meta( $attendee->ID, Meta::META_KEY_SEAT_TYPE, 'seat-type-id' );

		// Try to delete the attendee.
		$deleted = tribe( Attendee::class )->delete( $attendee->ID );

		// As the attendee deletion failed it should be same as original.
		$new_count = tec_tc_attendees()->by( 'event_id', $event_id )->count();
		$this->assertEquals( 2, $new_count, 'There should be 2 attendees' );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 98, $ticket->available(), 'There should be 98 tickets available' );
	}

	public function test_deleting_attendee_with_seats_and_reservation_cancel_success() {
		$controller = $this->make_controller();
		$controller->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with seated attendees',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		// setup mock data for reservation api call.
		update_post_meta( $event_id, Meta::META_KEY_UUID, 'test-post-uuid' );
		$this->set_oauth_token( 'auth-token' );

		// create ticket with default capacity of 100.
		$ticket_a_id = $this->create_tc_ticket( $event_id, 10 );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 100, $ticket->available(), 'There should be 100 tickets available' );

		// create order.
		$order = $this->create_order( [ $ticket_a_id => 2 ] );

		//get attendees.
		$attendees = tribe_attendees()->where( 'event_id', $event_id )->all();

		$this->assertEquals( 2, count( $attendees ), 'There should be 2 attendees' );

		$reservations = tribe( Reservations::class );

		$mock_reservation_cancel_failed = $this->mock_wp_remote(
			'post',
			$reservations->get_cancel_url(),
			[
				'headers' => [
					'Authorization' => 'Bearer auth-token',
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'eventId' => 'test-post-uuid',
						'ids'     => [
							'seat-reservation-id',
						],
					]
				),
			],
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

		// delete attendee.
		$attendee = $attendees[0];

		update_post_meta( $attendee->ID, Meta::META_KEY_RESERVATION_ID, 'seat-reservation-id' );
		update_post_meta( $attendee->ID, Meta::META_KEY_SEAT_TYPE, 'seat-type-id' );

		// Try to delete the attendee.
		$deleted = tribe( Attendee::class )->delete( $attendee->ID );

		// As the attendee deletion failed it should be same as original.
		$new_count = tec_tc_attendees()->by( 'event_id', $event_id )->count();
		$this->assertEquals( 1, $new_count, 'There should be 1 attendees' );

		// get ticket.
		$ticket = tribe( Module::class )->get_ticket( $event_id, $ticket_a_id );
		$this->assertEquals( 99, $ticket->available(), 'There should be 99 tickets available' );
	}

	/**
	 * @test
	 * @covers Attendee::include_seating_data
	 */
	public function test_attendee_has_seat_data() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with single seated attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$ticket_id = $this->create_tc_ticket( $event_id, 10 );

		$order     = $this->create_order(
			[ $ticket_id => 4 ],
			[
				'purchaser_email' => 'test-purchaser@test.com',
			]
		);
		$attendees = tribe_attendees()->by( 'event_id', $event_id )->by( 'order_status', [ 'completed' ] )->all();

		$this->make_controller()->register();
		// This is a regular attendee.
		$attendee_a = tec_tc_get_attendee( $attendees[0]->ID, ARRAY_A );
		// It should not have any seating data.
		$this->assertFalse( isset( $attendee_a['seat_label'] ) );
		$this->assertFalse( isset( $attendee_a['seat_type_id'] ) );
		$this->assertFalse( isset( $attendee_a['layout_id'] ) );

		// Make the ticket assigned seating.
		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		// Inject seating data into the attendee_b.
		update_post_meta( $attendees[1]->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-1' );

		$attendee_b = tec_tc_get_attendee( $attendees[1]->ID, ARRAY_A );

		$this->assertEquals( 'A-1', $attendee_b['seat_label'] );
		$this->assertFalse( isset( $attendee_b['seat_type_id'] ) );
		$this->assertFalse( isset( $attendee_b['layout_id'] ) );

		// Inject seating data into the attendee_c.
		update_post_meta( $attendees[2]->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'B-1' );
		update_post_meta( $attendees[2]->ID, Meta::META_KEY_SEAT_TYPE, 'vip-hash' );

		$attendee_c = tec_tc_get_attendee( $attendees[2]->ID, ARRAY_A );

		$this->assertEquals( 'B-1', $attendee_c['seat_label'] );
		$this->assertEquals( 'vip-hash', $attendee_c['seat_type_id'] );
		$this->assertFalse( isset( $attendee_c['layout_id'] ) );

		// Inject seating data into the attendee_d.
		update_post_meta( $attendees[3]->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'C-1' );
		update_post_meta( $attendees[3]->ID, Meta::META_KEY_SEAT_TYPE, 'general-admission-hash' );
		update_post_meta( $attendees[3]->ID, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$attendee_d = tec_tc_get_attendee( $attendees[3]->ID, ARRAY_A );

		$this->assertEquals( 'C-1', $attendee_d['seat_label'] );
		$this->assertEquals( 'general-admission-hash', $attendee_d['seat_type_id'] );
		$this->assertEquals( 'layout-id', $attendee_d['layout_id'] );
	}

	/**
	 * @test
	 * @covers Attendee::include_seat_info_in_email
	 */
	public function test_ticket_emails_has_seat_info() {
		$this->set_class_fn_return( 'Tribe__Tickets__Tickets', 'generate_security_code', 'SECURITY_CODE' );

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with single seated attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$ticket_id = $this->create_tc_ticket( $event_id, 10 );

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$order    = $this->create_order(
			[ $ticket_id => 1 ],
			[
				'purchaser_email' => 'test-purchaser@test.com',
			]
		);
		$attendee = tribe_attendees()->by( 'event_id', $event_id )->by( 'order_status', [ 'completed' ] )->first();

		update_post_meta( $attendee->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-1' );

		$html = '';

		add_filter(
			'tec_tickets_emails_dispatcher_content',
			function ( $content ) use ( &$html ) {
				$html = $content;

				// skip sending the email.
				return '';
			}
		);

		$this->make_controller()->register();

		$send = tribe( Module::class )->send_tickets_email_for_attendees( [ $attendee->ID ] );
		$html = str_replace(
			[ $event_id, $order->ID, $attendee->ID ],
			[
				'EVENT_ID',
				'ORDER_ID',
				'ATTENDEE_ID',
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @test
	 * @covers Attendee::include_seat_info_in_email
	 */
	public function test_ticket_emails_has_seat_info_for_multiple_attendees() {
		$this->set_class_fn_return( 'Tribe__Tickets__Tickets', 'generate_security_code', 'SECURITY_CODE' );

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with multiple seated attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$ticket_id = $this->create_tc_ticket( $event_id, 10 );

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );
		update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, 'some-seat-type' );

		$order = $this->create_order(
			[ $ticket_id => 2 ],
			[
				'purchaser_email' => 'test-purchaser@test.com',
			]
		);

		$attendees = tribe_attendees()->by( 'event_id', $event_id )->by( 'order_status', [ 'completed' ] )->all();

		update_post_meta( $attendees[0]->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-1' );
		update_post_meta( $attendees[1]->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-2' );

		$html = '';

		add_filter(
			'tec_tickets_emails_dispatcher_content',
			function ( $content ) use ( &$html ) {
				$html = $content;

				// skip sending the email.
				return '';
			}
		);

		$this->make_controller()->register();

		$send = tribe( Module::class )->send_tickets_email_for_attendees( [ $attendees[0]->ID, $attendees[1]->ID ] );

		$html = str_replace(
			[ $event_id, $order->ID, $attendees[0]->ID, $attendees[1]->ID ],
			[
				'EVENT_ID',
				'ORDER_ID',
				'ATTENDEE_ID_1',
				'ATTENDEE_ID_2',
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function my_tickets_page_data_provider(): Generator {
		yield 'regular post with tickets' => [
			function (): array {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post', 'tribe-events' ] );

				$post_id = static::factory()->post->create(
					[
						'post_type' => 'post',
					]
				);

				$ticket_id = $this->create_tc_ticket( $post_id, 10 );
				$order     = $this->create_order(
					[ $ticket_id => 1 ],
					[
						'purchaser_email' => 'test-purchaser@test.com',
					]
				);

				$attendee = tribe_attendees()
					->by( 'event_id', $post_id )
					->by( 'order_status', [ 'completed' ] )
					->first();

				return [ $post_id, [ $post_id, $ticket_id, $order->ID, $attendee->ID ] ];
			},
		];

		yield 'order with 1 regular tickets' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single seated attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id = $this->create_tc_ticket( $event_id, 10 );
				$order     = $this->create_order(
					[ $ticket_id => 1 ],
					[
						'purchaser_email' => 'test-purchaser@test.com',
					]
				);

				$attendee = tribe_attendees()
					->by( 'event_id', $event_id )
					->by( 'order_status', [ 'completed' ] )
					->first();

				return [ $event_id, [ $event_id, $order->ID, $ticket_id, $attendee->ID ] ];
			},
		];

		yield 'order with 1 seated tickets without assigned seat' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single seated attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

				$order = $this->create_order(
					[ $ticket_id => 1 ],
					[
						'purchaser_email' => 'test-purchaser@test.com',
					]
				);

				$attendee = tribe_attendees()
					->by( 'event_id', $event_id )
					->by( 'order_status', [ 'completed' ] )
					->first();

				return [ $event_id, [ $event_id, $order->ID, $ticket_id, $attendee->ID ] ];
			},
		];

		yield 'order with 1 seated tickets with assigned seat' => [
			function (): array {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Event with single seated attendee',
						'status'     => 'publish',
						'start_date' => '2020-01-01 00:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

				$ticket_id = $this->create_tc_ticket( $event_id, 10 );

				update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
				update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

				$order = $this->create_order(
					[ $ticket_id => 1 ],
					[
						'purchaser_email' => 'test-purchaser@test.com',
					]
				);

				$attendee = tribe_attendees()
					->by( 'event_id', $event_id )
					->by( 'order_status', [ 'completed' ] )
					->first();

				update_post_meta( $attendee->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-1' );

				return [ $event_id, [ $event_id, $order->ID, $ticket_id, $attendee->ID ] ];
			},
		];
	}

	/**
	 * @dataProvider my_tickets_page_data_provider
	 *
	 * @covers       Attendee::inject_seat_info_in_my_tickets
	 */
	public function test_my_tickets_page_has_seat_info( Closure $fixture ): void {
		[ $event_id, $post_ids ] = $fixture();

		$this->make_controller()->register();
		$view   = Tickets_View::instance();
		$orders = $view->get_event_attendees_by_order( $event_id, 0 );

		$template = tribe( 'tickets.editor.template' );
		$html     = $template->template(
			'tickets/my-tickets',
			[
				'title'   => 'Test My Tickets Page',
				'post_id' => $event_id,
				'orders'  => $orders,
				'post'    => get_post( $event_id ),
			],
			false
		);

		$html       = str_replace( $post_ids, array_fill( 0, count( $post_ids ), '{{ID}}' ), $html );
		$order_date = esc_html(
			Tribe__Date_Utils::reformat(
				current_time( 'mysql' ),
				Tribe__Date_Utils::DATEONLYFORMAT
			)
		);
		$html       = str_replace( $order_date, '{{order_date}}', $html );

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_fetch_attendees_by_post(): void {
		// Create a post with Tickets and Attendees, create a User to assign to the Attendees.
		$post_id  = self::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 20 );
		// Create an Order for 3 of Ticket 1, visitor user.
		$this->create_order( [ $ticket_1 => 3 ] );
		// Create an Order for 3 of Ticket 2.
		$purchaser = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $purchaser );
		$this->create_order( [ $ticket_2 => 3 ] );
		$data      = Tickets::get_attendees_by_args(
			[
				'per_page'           => 10,
				'return_total_found' => false,
				'order'              => 'DESC',
			],
			$post_id
		);
		$attendees = $data['attendees'];
		[
			$attendee_6,
			$attendee_5,
			$attendee_4,
			$attendee_3,
			$attendee_2,
			$attendee_1,
		]          = $attendees;
		update_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-1' );
		update_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-1-uuid' );
		update_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-1' );
		update_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-2' );
		update_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-1-uuid' );
		update_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-2' );
		update_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-3' );
		update_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-1-uuid' );
		update_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-3' );
		update_post_meta( $attendee_4, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-4' );
		update_post_meta( $attendee_4, Meta::META_KEY_SEAT_TYPE, 'seat-type-2-uuid' );
		update_post_meta( $attendee_4, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'B-1' );
		update_post_meta( $attendee_5, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-5' );
		update_post_meta( $attendee_5, Meta::META_KEY_SEAT_TYPE, 'seat-type-2-uuid' );
		update_post_meta( $attendee_5, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'B-2' );
		update_post_meta( $attendee_6, Meta::META_KEY_RESERVATION_ID, 'reservation-uuid-6' );
		update_post_meta( $attendee_6, Meta::META_KEY_SEAT_TYPE, 'seat-type-2-uuid' );
		update_post_meta( $attendee_6, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'B-3' );
		// Return 2 Attendees per page.
		add_filter( 'tec_tickets_seating_fetch_attendees_per_page', fn() => 2 );

		// Set up the request context.
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
		$_REQUEST['action']      = Ajax::ACTION_FETCH_ATTENDEES;
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );

		$controller = $this->make_controller();
		$controller->register();

		// Missing post ID.
		unset( $_REQUEST['postId'] );
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'You do not have permission to perform this action.',
				],
				403
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Request Attendees for a post that has none.
		$_REQUEST['postId']   = self::factory()->post->create();
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'attendees'    => [],
					'totalBatches' => 0,
					'currentBatch' => 1,
					'nextBatch'    => false,
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Request first batch of Attendees, but do not specify the current batch.
		$_REQUEST['postId'] = $post_id;
		unset( $_REQUEST['currentBatch'] );
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'attendees'    => tribe( Orders_Attendee::class )->format_many( [ $attendee_6, $attendee_5 ] ),
					'totalBatches' => 3,
					'currentBatch' => 1,
					'nextBatch'    => 2,
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Fetch second batch.
		$_REQUEST['currentBatch'] = 2;
		$wp_send_json_success     = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'attendees'    => tribe( Orders_Attendee::class )->format_many( [ $attendee_4, $attendee_3 ] ),
					'totalBatches' => 3,
					'currentBatch' => 2,
					'nextBatch'    => 3,
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Fetch third batch.
		$_REQUEST['currentBatch'] = 3;
		$wp_send_json_success     = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'attendees'    => tribe( Orders_Attendee::class )->format_many( [ $attendee_2, $attendee_1 ] ),
					'totalBatches' => 3,
					'currentBatch' => 3,
					'nextBatch'    => false,
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Try to fetch a 4th batch.
		$_REQUEST['currentBatch'] = 4;
		$wp_send_json_success     = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_FETCH_ATTENDEES );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'attendees'    => [],
					'totalBatches' => 0,
					'currentBatch' => 4,
					'nextBatch'    => false,
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();
	}

	public function test_update_reservation(): void {
		// Set up the request context.
		$administrator = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $administrator );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( Ajax::NONCE_ACTION );
		$_REQUEST['action']      = Ajax::ACTION_RESERVATION_CREATED;
		// Set up to mock the request body.
		$request_body = '';
		$this->set_fn_return(
			'file_get_contents',
			function ( $file ) use ( &$request_body ) {
				if ( $file === 'php://input' ) {
					return $request_body;
				}

				return file_get_contents( $file );
			},
			true
		);
		// Set up post, ticket, and attendees. Do that as visitor.
		$post_id                                  = self::factory()->post->create();
		$ticket                                   = $this->create_tc_ticket( $post_id );
		$order                                    = $this->create_order( [ $ticket => 3 ] )->ID;
		[ $attendee_1, $attendee_2, $attendee_3 ] = tribe_attendees()->by( 'event_id', $post_id )->get_ids();

		$controller = $this->make_controller();
		$controller->register();

		// Missing post ID from request context.
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'You do not have permission to perform this action.',
				],
				403
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Body is empty.
		$_REQUEST['postId'] = $post_id;
		$request_body       = '';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Body is not json.
		$request_body       = 'not-json';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Body is not valid json.
		$request_body       = '{"foo":"bar"}';
		$wp_send_json_error = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Invalid request body',
				],
				400
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();

		// Create attendee, do not send update to Attendee.
		$unset_tribe_tickets_get_ticket_provider_return = $this->set_fn_return(
			'tribe_tickets_get_ticket_provider',
			function ( $id ) use ( $attendee_1 ) {
				if ( $id === $attendee_1 ) {
					return new class() {
						public $class_name = Module::class;
						public function send_tickets_email_for_attendees() {
							throw new AssertionFailedError( 'Should not send email' );
						}
					};
				}

				return tribe_tickets_get_ticket_provider( $id );
			},
			true
		);
		$request_body                                   = wp_json_encode(
			[
				'attendeeId'           => $attendee_1,
				'reservationId'        => 'reservation-uuid-1',
				'seatTypeId'           => 'seat-type-uuid-1',
				'seatLabel'            => 'A-1',
				'sendUpdateToAttendee' => false,
			]
		);
		$wp_send_json_success                           = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'id'            => $attendee_1,
					'name'          => 'Test Purchaser',
					'purchaser'     =>
						[
							'id'                  => $order,
							'name'                => 'Test Purchaser',
							'associatedAttendees' => 3,
						],
					'ticketId'      => $ticket,
					'ticketName'    => "Test TC ticket for {$post_id}",
					'seatTypeId'    => 'seat-type-uuid-1',
					'seatLabel'     => 'A-1',
					'reservationId' => 'reservation-uuid-1',
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->assertEquals( 'reservation-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'A-1', get_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$this->reset_wp_send_json_mocks();
		$unset_tribe_tickets_get_ticket_provider_return();

		// Create attendee, do send update to Attendee.
		$request_body         = wp_json_encode(
			[
				'attendeeId'           => $attendee_2,
				'reservationId'        => 'reservation-uuid-2',
				'seatTypeId'           => 'seat-type-uuid-1',
				'seatLabel'            => 'A-2',
				'sendUpdateToAttendee' => true,
			]
		);
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'id'            => $attendee_2,
					'name'          => 'Test Purchaser',
					'purchaser'     =>
						[
							'id'                  => $order,
							'name'                => 'Test Purchaser',
							'associatedAttendees' => 3,
						],
					'ticketId'      => $ticket,
					'ticketName'    => "Test TC ticket for {$post_id}",
					'seatTypeId'    => 'seat-type-uuid-1',
					'seatLabel'     => 'A-2',
					'reservationId' => 'reservation-uuid-2',
				],
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();
		$this->assertEquals( 'reservation-uuid-2', get_post_meta( $attendee_2, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_2, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'A-2', get_post_meta( $attendee_2, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );

		// Failure to send update to Attendee.
		$request_body                                   = wp_json_encode(
			[
				'attendeeId'           => $attendee_3,
				'reservationId'        => 'reservation-uuid-3',
				'seatTypeId'           => 'seat-type-uuid-1',
				'seatLabel'            => 'A-3',
				'sendUpdateToAttendee' => true,
			]
		);
		$unset_tribe_tickets_get_ticket_provider_return = $this->set_fn_return(
			'tribe_tickets_get_ticket_provider',
			function ( $id ) use ( $attendee_3 ) {
				if ( $id === $attendee_3 ) {
					return new class() {
						public $class_name = Module::class;
						public function send_tickets_email_for_attendees() {
							return false;
						}
					};
				}

				return tribe_tickets_get_ticket_provider( $id );
			},
			true
		);
		$wp_send_json_error                             = $this->mock_wp_send_json_error();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_CREATED );
		$this->assertTrue(
			$wp_send_json_error->was_called_times_with(
				1,
				[
					'error' => 'Failed to send the update mail.',
				]
			),
			$wp_send_json_error->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();
		$this->assertEquals( 'reservation-uuid-3', get_post_meta( $attendee_3, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-1', get_post_meta( $attendee_3, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'A-3', get_post_meta( $attendee_3, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$unset_tribe_tickets_get_ticket_provider_return();

		// Update Attendee 1 reservation, do not send the update to Attendee.
		$request_body                                   = wp_json_encode(
			[
				'attendeeId'           => $attendee_1,
				'reservationId'        => 'reservation-uuid-1',
				'seatTypeId'           => 'seat-type-uuid-2',
				'seatLabel'            => 'B-4',
				'sendUpdateToAttendee' => false,
			]
		);
		$unset_tribe_tickets_get_ticket_provider_return = $this->set_fn_return(
			'tribe_tickets_get_ticket_provider',
			function ( $id ) use ( $attendee_1 ) {
				if ( $id === $attendee_1 ) {
					return new class() {
						public $class_name = Module::class;
						public function send_tickets_email_for_attendees() {
							throw new AssertionFailedError( 'Should not send email' );
						}
					};
				}

				return tribe_tickets_get_ticket_provider( $id );
			},
			true
		);
		$wp_send_json_success                           = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_UPDATED );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'id'            => $attendee_1,
					'name'          => 'Test Purchaser',
					'purchaser'     =>
						[
							'id'                  => $order,
							'name'                => 'Test Purchaser',
							'associatedAttendees' => 3,
						],
					'ticketId'      => $ticket,
					'ticketName'    => "Test TC ticket for {$post_id}",
					'seatTypeId'    => 'seat-type-uuid-2',
					'seatLabel'     => 'B-4',
					'reservationId' => 'reservation-uuid-1',
				]
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();
		$this->assertEquals( 'reservation-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-2', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'B-4', get_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
		$unset_tribe_tickets_get_ticket_provider_return();

		// Update Attendee 1 reservation again, this time send the update to Attendee.
		$request_body         = wp_json_encode(
			[
				'attendeeId'           => $attendee_1,
				'reservationId'        => 'reservation-uuid-1',
				'seatTypeId'           => 'seat-type-uuid-3',
				'seatLabel'            => 'C-5',
				'sendUpdateToAttendee' => true,
			]
		);
		$wp_send_json_success = $this->mock_wp_send_json_success();
		do_action( 'wp_ajax_' . Ajax::ACTION_RESERVATION_UPDATED );
		$this->assertTrue(
			$wp_send_json_success->was_called_times_with(
				1,
				[
					'id'            => $attendee_1,
					'name'          => 'Test Purchaser',
					'purchaser'     =>
						[
							'id'                  => $order,
							'name'                => 'Test Purchaser',
							'associatedAttendees' => 3,
						],
					'ticketId'      => $ticket,
					'ticketName'    => "Test TC ticket for {$post_id}",
					'seatTypeId'    => 'seat-type-uuid-3',
					'seatLabel'     => 'C-5',
					'reservationId' => 'reservation-uuid-1',
				]
			),
			$wp_send_json_success->get_calls_as_string()
		);
		$this->reset_wp_send_json_mocks();
		$this->assertEquals( 'reservation-uuid-1', get_post_meta( $attendee_1, Meta::META_KEY_RESERVATION_ID, true ) );
		$this->assertEquals( 'seat-type-uuid-3', get_post_meta( $attendee_1, Meta::META_KEY_SEAT_TYPE, true ) );
		$this->assertEquals( 'C-5', get_post_meta( $attendee_1, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true ) );
	}

	public function tests_seats_row_actions_added() {
		$this->set_fn_return( 'is_admin', true );
		$this->make_controller()->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with single seated attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$ticket_id = $this->create_tc_ticket( $event_id, 10 );

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );

		$order = $this->create_order(
			[ $ticket_id => 1 ],
			[
				'purchaser_email' => 'test-purchaser@test.com',
			]
		);
		global $post;
		$post = get_post( $event_id );

		$row_actions = apply_filters( 'post_row_actions', [], $post );

		$this->assertContains( 'tickets_seats', array_keys( $row_actions ) );
		$json = str_replace(
			$event_id,
			'{{EVENT_ID}}',
			wp_json_encode( $row_actions, JSON_SNAPSHOT_OPTIONS )
		);

		$this->assertMatchesJsonSnapshot( $json );

		// Removing the layout enabled meta should remove the row action.
		delete_post_meta( $event_id, Meta::META_KEY_ENABLED );

		$row_actions = apply_filters( 'post_row_actions', [], $post );
		$this->assertNotContains( 'tickets_seats', array_keys( $row_actions ) );
	}

	public function test_tc_order_success_page_has_seat_label() {
		$shortcode_manager = new Manager();
		$shortcode_manager->add_shortcodes();

		$this->make_controller()->register();

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with single seated attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'some-layout' );

		$ticket_id = $this->create_tc_ticket( $event_id, 10 );
		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, 'some-seat' );

		$order = $this->create_order(
			[ $ticket_id => 1 ],
			[
				'purchaser_email' => 'test-purchaser@test.com',
				'post_date'       => '2022-01-21 00:00:00',
			]
		);
		update_post_meta( $order->ID, '_tec_tc_order_gateway_order_id', $order->ID );
		clean_post_cache( $order->ID );

		$attendee = tribe_attendees()
			->by( 'event_id', $event_id )
			->by( 'order_status', [ 'completed' ] )
			->first();

		// Now add the seat label meta data to attendee.
		update_post_meta( $attendee->ID, Meta::META_KEY_SEAT_TYPE, 'some-seat-type' );
		update_post_meta( $attendee->ID, Meta::META_KEY_ATTENDEE_SEAT_LABEL, 'A-1' );
		clean_post_cache( $attendee->ID );

		$_REQUEST['tc-order-id'] = $order->ID;

		$shortcode = Success_Shortcode::get_wp_slug();
		$html      = do_shortcode( "[{$shortcode}]" );
		$html      = str_replace(
			[ $event_id, $order->ID, $attendee->ID ],
			[ '{EVENT_ID}', '{ORDER_ID}', '{ATTENDEE_ID}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function test_format_many_should_skip_non_tc_provider_attendees() {
		// Create a post with Tickets and Attendees, create a User to assign to the Attendees.
		$post_id  = self::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 20 );
		// Create an Order for 3 of Ticket 1, visitor user.
		$this->create_order(
			[
				$ticket_1 => 3,
				$ticket_2 => 3,
			]
		);

		$rsvp_ticket = $this->create_rsvp_ticket( $post_id );

		[
			$rsvp_attendee_1,
			$rsvp_attendee_2,
		] = $this->create_many_attendees_for_ticket( 2, $rsvp_ticket, $post_id );

		$data      = Tickets::get_attendees_by_args(
			[
				'per_page'           => 10,
				'return_total_found' => false,
				'order'              => 'DESC',
			],
			$post_id
		);
		$attendees = $data['attendees'];

		// Make sure we have 8 attendees in total.
		$this->assertEquals( 8, count( $attendees ) );

		$formatted = tribe( Orders_Attendee::class )->format_many( $attendees );

		// Make sure we only have 6 attendees formatted, as the other 2 are not from TC provider.
		$this->assertEquals( 6, count( $formatted ) );
	}

	public function test_it_adjusts_attendee_page_render_context_for_seating() {
		Seat_Types::insert_many(
			[
				[
					'id'     => 'some-seat-1',
					'name'   => 'A',
					'seats'  => 10,
					'map'    => 'some-map-1',
					'layout' => 'some-layout',
				],
				[
					'id'     => 'some-seat-2',
					'name'   => 'B',
					'seats'  => 20,
					'map'    => 'some-map-1',
					'layout' => 'some-layout',
				],
				[
					'id'     => 'some-seat-3',
					'name'   => 'C',
					'seats'  => 30,
					'map'    => 'some-map-1',
					'layout' => 'some-layout',
				],
			]
		);

		// Create ticket-able post and tickets
		$post_id  = self::factory()->post->create();
		$ticket_1 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 10,
				],
			]
		);
		$ticket_2 = $this->create_tc_ticket(
			$post_id,
			20,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 10,
				],
			]
		);
		$ticket_3 = $this->create_tc_ticket(
			$post_id,
			30,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);
		$ticket_4 = $this->create_tc_ticket(
			$post_id,
			40,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 20,
				],
			]
		);
		$ticket_5 = $this->create_tc_ticket(
			$post_id,
			50,
			[
				'tribe-ticket' => [
					'mode'     => Global_Stock::CAPPED_STOCK_MODE,
					'capacity' => 30,
				],
			]
		);

		// Set up meta of post and tickets.
		update_post_meta( $post_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout' );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		update_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, 60 );
		update_post_meta( $post_id, tribe( 'tickets.handler' )->key_capacity, 60 );

		update_post_meta( $ticket_1, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'some-seat-1' );
		update_post_meta( $ticket_2, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'some-seat-1' );
		update_post_meta( $ticket_3, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'some-seat-2' );
		update_post_meta( $ticket_4, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_4, Meta::META_KEY_SEAT_TYPE, 'some-seat-2' );
		update_post_meta( $ticket_5, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_5, Meta::META_KEY_SEAT_TYPE, 'some-seat-3' );

		// Sanity check
		$this->assertEquals( 60, get_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 60, get_post_meta( $post_id, tribe( 'tickets.handler' )->key_capacity, true ) );

		$this->assertEquals( 10, get_post_meta( $ticket_1, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 10, get_post_meta( $ticket_1, '_stock', true ) );

		$this->assertEquals( 10, get_post_meta( $ticket_2, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 10, get_post_meta( $ticket_2, '_stock', true ) );

		$this->assertEquals( 20, get_post_meta( $ticket_3, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 20, get_post_meta( $ticket_3, '_stock', true ) );

		$this->assertEquals( 20, get_post_meta( $ticket_4, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 20, get_post_meta( $ticket_4, '_stock', true ) );

		$this->assertEquals( 30, get_post_meta( $ticket_5, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 30, get_post_meta( $ticket_5, '_stock', true ) );

		// Create an order.
		$this->create_order(
			[
				$ticket_2 => 2,
				$ticket_4 => 4,
				$ticket_5 => 5,
			]
		);

		$this->assertEquals( 49, get_post_meta( $post_id, Global_Stock::GLOBAL_STOCK_LEVEL, true ) );
		$this->assertEquals( 60, get_post_meta( $post_id, tribe( 'tickets.handler' )->key_capacity, true ) );

		$this->assertEquals( 10, get_post_meta( $ticket_1, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 8, get_post_meta( $ticket_1, '_stock', true ) );

		$this->assertEquals( 10, get_post_meta( $ticket_2, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 8, get_post_meta( $ticket_2, '_stock', true ) );

		$this->assertEquals( 20, get_post_meta( $ticket_3, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 16, get_post_meta( $ticket_3, '_stock', true ) );

		$this->assertEquals( 20, get_post_meta( $ticket_4, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 16, get_post_meta( $ticket_4, '_stock', true ) );

		$this->assertEquals( 30, get_post_meta( $ticket_5, tribe( 'tickets.handler' )->key_capacity, true ) );
		$this->assertEquals( 25, get_post_meta( $ticket_5, '_stock', true ) );

		$this->make_controller()->register();

		$this->assertEquals(
			[
				'ticket_totals' => [
					'available' => 8 + 16 + 25,
				]
			],
			apply_filters(
				'tec_tickets_attendees_page_render_context',
				[],
				$post_id,
				array_map( [ Tickets::class, 'load_ticket_object' ], [ $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ] )
			)
		);
	}
}
