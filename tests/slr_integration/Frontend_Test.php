<?php

namespace TEC\Tickets\Seating\Frontend;

use Closure;
use Generator;
use PHPUnit\Framework\Assert;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Tickets_View;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\OAuth_Token;
use TEC\Tickets\Seating\Service\Service;
use TEC\Tickets\Seating\Service\Service_Status;
use TEC\Tickets\Seating\Tables\Sessions;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\Reservations_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Common\StellarWP\Assets\Assets;
use WP_Query;

class Frontend_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Order_Maker;
	use Series_Pass_Factory;
	use With_Uopz;
	use With_Clock_Mock;
	use With_Tickets_Commerce;
	use OAuth_Token;
	use Reservations_Maker;

	protected string $controller_class = Frontend::class;

	public function should_enqueue_assets_data_provider(): Generator {
		yield 'empty ticketable post types' => [
			function (): bool {
				tribe_update_option( 'ticket-enabled-post-types', [] );

				return false;
			}
		];

		yield 'not singular' => [
			function () {
				tribe_update_option( 'ticket-enabled-post-types', ['post', 'page'] );
				$this->set_fn_return( 'is_singular', false );

				return false;
			},
		];

		yield 'not ticket-able' => [
			function () {
				tribe_update_option( 'ticket-enabled-post-types', [ 'post'] );
				$page_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				$this->set_fn_return( 'is_singular', true );

				return false;
			},
		];

		yield 'ticket-able, not seating' => [
			function () {
				tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				$this->set_fn_return( 'is_singular', true );

				return false;
			},
		];

		yield 'ticket-able, seating' => [
			function () {
				tribe_update_option( 'ticket-enabled-post-types', [ 'page', 'post' ] );
				$post_id = static::factory()->post->create();
				update_post_meta( $post_id, Meta::META_KEY_ENABLED, '1' );
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'layout-id' );
				$GLOBALS['post'] = $post_id;
				$this->set_fn_return( 'is_singular', true );

				return true;
			},
		];
	}

	/**
	 * @dataProvider should_enqueue_assets_data_provider
	 */
	public function test_should_enqueue_assets( Closure $fixture ): void {
		$should_enqueue_assets = $fixture();

		$controller = $this->make_controller();

		$this->assertEquals( $should_enqueue_assets, $controller->should_enqueue_assets() );
	}

	/**
	 * it should_display_ticket_block_when_seating_is_enabled
	 *
	 * @test
	 */
	public function should_display_ticket_block_when_seating_is_not_enabled() {
		$post_id = static::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		delete_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID );
		$ticket_1 = $this->create_tc_ticket( $post_id, 10 );
		$ticket_2 = $this->create_tc_ticket( $post_id, 30 );
		// Sort the tickets "manually".
		wp_update_post(
			[
				'ID'         => $ticket_1,
				'menu_order' => 1,
			]
		);
		wp_update_post(
			[
				'ID'         => $ticket_2,
				'menu_order' => 2,
			]
		);

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, $ticket_1, $ticket_2 ],
			[ '{{post_id}}', '{{ticket_1}}', '{{ticket_2}}' ],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	/**
	 * @dataProvider tickets_capacity_data_provider
	 *
	 * @covers       Controller::adjust_events_ticket_capacity_for_seating
	 */
	public function test_capacity_should_account_for_seating( Closure $fixture ): void {
		[ $event_id, $expected_capacity ] = $fixture();

		$controller = $this->make_controller();
		$this->assertEquals( $expected_capacity, $controller->get_events_ticket_capacity_for_seating( $event_id ) );
	}

	public function tickets_capacity_data_provider(): Generator {
		yield 'single event with 3 Tickets 0 sharing' => [
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

				$data = [
					'tribe-ticket' => [
						'mode'     => Global_Stock::CAPPED_STOCK_MODE,
						'capacity' => 100,
					],
				];

				$ticket_id1 = $this->create_tc_ticket( $event_id, 1, $data );
				$ticket_id2 = $this->create_tc_ticket( $event_id, 1, $data );
				$ticket_id3 = $this->create_tc_ticket( $event_id, 1, $data );

				update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

				update_post_meta( $ticket_id1, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id1, '_stock', 70 );
				update_post_meta( $ticket_id1, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id1, '_stock_status', 'instock' );
				update_post_meta( $ticket_id1, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id1 ) );

				update_post_meta( $ticket_id2, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id2, '_stock', 70 );
				update_post_meta( $ticket_id2, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id2, '_stock_status', 'instock' );
				update_post_meta( $ticket_id2, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id2 ) );

				update_post_meta( $ticket_id3, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id3, '_stock', 10 );
				update_post_meta( $ticket_id3, '_tribe_ticket_capacity', 10 );
				update_post_meta( $ticket_id3, '_stock_status', 'instock' );
				update_post_meta( $ticket_id3, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id3 ) );

				return [ $event_id, 70 + 70 + 10 ];
			},
		];
		yield 'single event with 3 Tickets 2 sharing' => [
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

				$data = [
					'tribe-ticket' => [
						'mode'     => Global_Stock::CAPPED_STOCK_MODE,
						'capacity' => 100,
					],
				];

				$ticket_id1 = $this->create_tc_ticket( $event_id, 1, $data );
				$ticket_id2 = $this->create_tc_ticket( $event_id, 1, $data );
				$ticket_id3 = $this->create_tc_ticket( $event_id, 1, $data );

				update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

				update_post_meta( $ticket_id1, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id1, '_stock', 70 );
				update_post_meta( $ticket_id1, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id1, '_stock_status', 'instock' );
				update_post_meta( $ticket_id1, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id1 ) );

				update_post_meta( $ticket_id2, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id2, '_stock', 70 );
				update_post_meta( $ticket_id2, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id2, '_stock_status', 'instock' );
				update_post_meta( $ticket_id2, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id1 ) );

				update_post_meta( $ticket_id3, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id3, '_stock', 10 );
				update_post_meta( $ticket_id3, '_tribe_ticket_capacity', 10 );
				update_post_meta( $ticket_id3, '_stock_status', 'instock' );
				update_post_meta( $ticket_id3, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id3 ) );

				return [ $event_id, 70 + 10 ];
			},
		];
		yield 'single event with 3 Tickets 3 sharing' => [
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

				$data = [
					'tribe-ticket' => [
						'mode'     => Global_Stock::CAPPED_STOCK_MODE,
						'capacity' => 100,
					],
				];

				$ticket_id1 = $this->create_tc_ticket( $event_id, 1, $data );
				$ticket_id2 = $this->create_tc_ticket( $event_id, 1, $data );
				$ticket_id3 = $this->create_tc_ticket( $event_id, 1, $data );

				update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

				update_post_meta( $ticket_id1, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id1, '_stock', 70 );
				update_post_meta( $ticket_id1, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id1, '_stock_status', 'instock' );
				update_post_meta( $ticket_id1, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id1 ) );

				update_post_meta( $ticket_id2, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id2, '_stock', 70 );
				update_post_meta( $ticket_id2, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id2, '_stock_status', 'instock' );
				update_post_meta( $ticket_id2, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id1 ) );

				update_post_meta( $ticket_id3, '_manage_stock', 'yes' );
				update_post_meta( $ticket_id3, '_stock', 70 );
				update_post_meta( $ticket_id3, '_tribe_ticket_capacity', 70 );
				update_post_meta( $ticket_id3, '_stock_status', 'instock' );
				update_post_meta( $ticket_id3, Meta::META_KEY_SEAT_TYPE, md5( $ticket_id1 ) );

				return [ $event_id, 70 ];
			},
		];
	}

	public function seating_enabled_fixtures(): Generator {
		yield 'one ticket' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket( $post_id, 20 );

				return [ $post_id, $ticket ];
			},
		];

		yield 'two tickets' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket_1 = $this->create_tc_ticket( $post_id, 20 );
				$ticket_2 = $this->create_tc_ticket( $post_id, 50 );
				// Sort the tickets "manually".
				foreach ( [ $ticket_1, $ticket_2 ] as $k => $ticket ) {
					wp_update_post(
						[
							'ID'         => $ticket,
							'menu_order' => $k,
						]
					);
				}

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket_1, $ticket_2 ];
			},
		];


		yield 'five tickets' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket_1 = $this->create_tc_ticket( $post_id, 20 );
				$ticket_2 = $this->create_tc_ticket( $post_id, 50 );
				$ticket_3 = $this->create_tc_ticket( $post_id, 10 );
				$ticket_4 = $this->create_tc_ticket( $post_id, 30 );
				$ticket_5 = $this->create_tc_ticket( $post_id, 10 );
				// Sort the tickets "manually".
				foreach ( [ $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ] as $k => $ticket ) {
					wp_update_post(
						[
							'ID'         => $ticket,
							'menu_order' => $k,
						]
					);
				}

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket_1, $ticket_2, $ticket_3, $ticket_4, $ticket_5 ];
			},
		];

		yield 'ticket with past date' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket(
					$post_id,
					10,
					[
						'ticket_start_date' => '2024-01-01',
						'ticket_start_time' => '08:00:00',
						'ticket_end_date'   => '2024-03-01',
						'ticket_end_time'   => '20:00:00',
					]
				);

				return [ $post_id, $ticket ];
			},
		];

		yield 'ticket with future date' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_start_date' => '2044-01-01',
						'ticket_start_time' => '08:00:00',
						'ticket_end_date'   => '2044-03-01',
						'ticket_end_time'   => '20:00:00',
					]
				);

				return [ $post_id, $ticket ];
			},
		];

		yield 'ticket with future and past' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'ticket_start_date' => '2044-01-01',
						'ticket_start_time' => '08:00:00',
						'ticket_end_date'   => '2044-03-01',
						'ticket_end_time'   => '20:00:00',
					]
				);

				$ticket_2 = $this->create_tc_ticket(
					$post_id,
					10,
					[
						'ticket_start_date' => '2024-01-01',
						'ticket_start_time' => '08:00:00',
						'ticket_end_date'   => '2024-03-01',
						'ticket_end_time'   => '20:00:00',
					]
				);

				return [ $post_id, $ticket, $ticket_2 ];
			},
		];

		yield 'sold out event' => [
			function () {
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);

				update_post_meta( $post_id, Meta::META_KEY_ENABLED, 1 );
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 5 );
				$ticket = $this->create_tc_ticket(
					$post_id,
					20,
					[
						'tribe-ticket' => [
							'mode'     => Global_Stock::CAPPED_STOCK_MODE,
							'capacity' => 5,
						],
					]
				);

				$order = $this->create_order(
					[
						$ticket => 5,
					]
				);

				return [ $post_id, $ticket ];
			},
		];

		yield 'service down' => [
			function () {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::SERVICE_UNREACHABLE );
				}, 1000, 2 );
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket ];
			}
		];

		yield 'service not connected' => [
			function () {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::NOT_CONNECTED );
				}, 1000, 2 );
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket ];
			}
		];

		yield 'invalid license' => [
			function () {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::INVALID_LICENSE );
				}, 1000, 2 );
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket ];
			}
		];

		yield 'no license' => [
			function () {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::NO_LICENSE );
				}, 1000, 2 );
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket ];
			}
		];

		yield 'expired license' => [
			function () {
				add_filter( 'tec_tickets_seating_service_status', function ( $_status, $backend_base_url ) {
					return new Service_Status( $backend_base_url, Service_Status::EXPIRED_LICENSE );
				}, 1000, 2 );
				$post_id = static::factory()->post->create(
					[
						'post_type' => 'page',
					]
				);
				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );
				/**
				 * @var Tickets_Handler $tickets_handler
				 */
				$tickets_handler   = tribe( 'tickets.handler' );
				$capacity_meta_key = $tickets_handler->key_capacity;
				update_post_meta( $post_id, $capacity_meta_key, 100 );
				$ticket = $this->create_tc_ticket( $post_id, 20 );

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'some-layout-uuid' );

				return [ $post_id, $ticket ];
			}
		];
	}

	/**
	 * it should_replace_ticket_block_when_seating_is_enabled
	 *
	 * @test
	 * @dataProvider seating_enabled_fixtures
	 */
	public function should_replace_ticket_block_when_seating_is_enabled( Closure $fixture ) {
		$this->test_services->singleton(
			Service::class,
			function () {
				return $this->make(
					Service::class,
					[
						'frontend_base_url'   => 'https://service.test.local',
						'backend_base_url'   => 'https://service.test.local',
						'get_ephemeral_token' => function ( $expiration, $scope ) {
							Assert::assertEquals( HOUR_IN_SECONDS, $expiration );
							Assert::assertEquals( 'visitor', $scope );

							return 'test-ephemeral-token';
						},
						'get_post_uuid'       => 'test-post-uuid',
					]
				);
			}
		);
		$ids     = $fixture();
		$post_id = array_shift( $ids );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, ...$ids ],
			[
				'{{post_id}}',
				...array_map(
					function ( $id ) {
						return '{{ticket_' . $id . '}}';
					},
					range( 1, count( $ids ) )
				),
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
	}

	public function asset_data_provider() {
		$assets = [
			'tec-tickets-seating-frontend'       => '/build/Seating/frontend/ticketsBlock.js',
			'tec-tickets-seating-frontend-style' => '/build/Seating/frontend/ticketsBlock.css',
		];

		foreach ( $assets as $slug => $path ) {
			yield $slug => [ $slug, $path ];
		}
	}

	/**
	 * @test
	 * @dataProvider asset_data_provider
	 */
	public function it_should_locate_assets_where_expected( $slug, $path ) {
		$this->make_controller()->register();

		$this->assertTrue( Assets::init()->exists( $slug ) );

		// We use false, because in CI mode the assets are not build so min aren't available. Its enough to check that the non-min is as expected.
		$asset_url = Assets::init()->get( $slug )->get_url( false );
		$this->assertEquals( plugins_url( $path, EVENT_TICKETS_MAIN_PLUGIN_FILE ), $asset_url );
	}

	/**
	 * @dataProvider seating_enabled_fixtures
	 */
	public function test_get_ticket_block_data( Closure $fixture ) {
		$this->set_fn_return( 'wp_create_nonce', '1111111111' );
		$ids     = $fixture();
		$post_id = array_shift( $ids );
		foreach ( $ids as $k => $id ) {
			$l = $k % 3;
			update_post_meta( $id, Meta::META_KEY_SEAT_TYPE, "uuid-seat-type-{$l}" );
		}

		$controller = $this->make_controller();
		$controller->register();
		$data = $controller->get_ticket_block_data( $post_id );

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );
		// Replace the ticket IDs with placeholders.
		$json = str_replace(
			[ $post_id, ...$ids ],
			[
				'{{post_id}}',
				...array_map(
					function ( $id ) {
						return '{{ticket_' . $id . '}}';
					},
					range( 1, count( $ids ) )
				),
			],
			$json
		);

		$this->assertMatchesJsonSnapshot( $json );
	}

	public function test_get_ticket_block_data_with_tickets_not_in_range(): void {
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		$post_id = self::factory()->post->create();
		// Create a first ticket that ended sales beforee the current time.
		$ticket_1 = $this->create_tc_ticket(
			$post_id,
			10,
			[
				'ticket_start_date' => '2024-01-01',
				'ticket_start_time' => '08:00:00',
				'ticket_end_date'   => '2024-03-01',
				'ticket_end_time'   => '20:00:00',
			]
		);
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		// Create a second ticket that opens sales after the current time.
		$ticket_2 = $this->create_tc_ticket(
			$post_id,
			20,
			[
				'ticket_start_date' => '2024-04-01',
				'ticket_start_time' => '08:00:00',
				'ticket_end_date'   => '2024-04-30',
				'ticket_end_time'   => '20:00:00',
			]
		);
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		// Create a third ticket that is in range.
		$ticket_3 = $this->create_tc_ticket(
			$post_id,
			30,
			[
				'ticket_start_date' => '2024-03-01',
				'ticket_start_time' => '08:00:00',
				'ticket_end_date'   => '2024-03-30',
				'ticket_end_time'   => '20:00:00',
			]
		);
		update_post_meta( $ticket_3, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		// Freeze time to 2024-03-23 12:34:00.
		$this->freeze_time( Dates::immutable( '2024-03-23 12:34:00' ) );

		$controller = $this->make_controller();
		$controller->register();
		$data = $controller->get_ticket_block_data( $post_id );

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );

		// Replace the ticket IDs with placeholders.
		$json = str_replace(
			[ $post_id, $ticket_1, $ticket_2, $ticket_3 ],
			[
				'{{post_id}}',
				'{{ticket_1}}',
				'{{ticket_2}}',
				'{{ticket_3}}',
			],
			$json
		);
		$this->assertMatchesJsonSnapshot( $json );
	}

	public function test_should_add_seat_selected_labels_per_ticket_attribute() {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Event with single attendee',
				'status'     => 'publish',
				'start_date' => '2020-01-01 00:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Meta::META_KEY_ENABLED, true );

		$data = [
			'tribe-ticket' => [
				'mode'     => Global_Stock::CAPPED_STOCK_MODE,
				'capacity' => 100,
			],
		];

		$ticket_id = $this->create_tc_ticket( $event_id, 1, $data );

		update_post_meta( $ticket_id, Meta::META_KEY_ENABLED, true );
		update_post_meta( $ticket_id, Meta::META_KEY_SEAT_TYPE, 'seat-type-' . $ticket_id );

		$sessions = tribe( Sessions::class );
		$this->set_oauth_token( 'auth-token' );

		$session = tribe( Session::class );

		$this->assertNull( $session->get_session_token_object_id() );

		$session->add_entry( $event_id, 'test-token-1' );
		$sessions->upsert( 'test-token-1', $event_id, time() + 100 );
		$sessions->update_reservations( 'test-token-1', $this->create_mock_reservations_data( [ $ticket_id ], 2 ) );

		$this->assertEquals( [ 'test-token-1', $event_id ], $session->get_session_token_object_id() );

		$this->make_controller()->register();

		$ticket = Tickets::load_ticket_object( $ticket_id );

		$attributes = apply_filters( 'tribe_tickets_block_ticket_html_attributes', [], $ticket, $event_id );

		$this->assertEmpty( $attributes );

		update_post_meta( $event_id, Meta::META_KEY_LAYOUT_ID, 'layout-uuid-1' );

		$attributes = apply_filters( 'tribe_tickets_block_ticket_html_attributes', [], $ticket, $event_id );

		$this->assertEquals( esc_attr( implode( ',', [ 'seat-label-0-1' , 'seat-label-0-2' ] ) ), $attributes['data-seat-labels'] );
	}
}
