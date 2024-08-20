<?php

namespace TEC\Tickets\Seating\Frontend;

use Closure;
use Generator;
use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Tickets_View;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Service;
use Tribe\Tests\Traits\With_Clock_Mock;
use Tribe\Tests\Traits\With_Uopz;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe\Tickets\Test\Traits\With_Tickets_Commerce;
use Tribe__Date_Utils as Dates;

class Frontend_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Series_Pass_Factory;
	use With_Uopz;
	use With_Clock_Mock;
	use With_Tickets_Commerce;

	protected string $controller_class = Frontend::class;

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
			}
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
			}
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
		$this->test_services->singleton( Service::class, function () {
			return $this->make( Service::class, [
				'frontend_base_url'   => 'https://service.test.local',
				'get_ephemeral_token' => 'test-ephemeral-token',
				'get_post_uuid'       => 'test-post-uuid',
			] );
		} );
		$ids     = $fixture();
		$post_id = array_shift( $ids );

		$this->make_controller()->register();

		$html = tribe( Tickets_View::class )->get_tickets_block( $post_id );

		// Replace the ticket IDs with placeholders.
		$html = str_replace(
			[ $post_id, ...$ids ],
			[
				'{{post_id}}',
				...array_map( function ( $id ) {
					return '{{ticket_' . $id . '}}';
				}, range( 1, count( $ids ) ) )
			],
			$html
		);

		$this->assertMatchesHtmlSnapshot( $html );
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
				...array_map( function ( $id ) {
					return '{{ticket_' . $id . '}}';
				}, range( 1, count( $ids ) ) )
			],
			$json
		);

		$this->assertMatchesJsonSnapshot( $json );
	}

	public function test_get_ticket_block_data_with_tickets_not_in_range():void{
		$this->set_fn_return( 'wp_create_nonce', '1234567890' );
		$post_id = self::factory()->post->create();
		// Create a first ticket that ended sales beforee the current time.
		$ticket_1 = $this->create_tc_ticket( $post_id, 10, [
			'ticket_start_date' => '2024-01-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2024-03-01',
			'ticket_end_time'   => '20:00:00',
		] );
		update_post_meta( $ticket_1, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-1' );
		// Create a second ticket that opens sales after the current time.
		$ticket_2 = $this->create_tc_ticket( $post_id, 20, [
			'ticket_start_date' => '2024-04-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2024-04-30',
			'ticket_end_time'   => '20:00:00',
		] );
		update_post_meta( $ticket_2, Meta::META_KEY_SEAT_TYPE, 'seat-type-uuid-2' );
		// Create a third ticket that is in range.
		$ticket_3 = $this->create_tc_ticket( $post_id, 30, [
			'ticket_start_date' => '2024-03-01',
			'ticket_start_time' => '08:00:00',
			'ticket_end_date'   => '2024-03-30',
			'ticket_end_time'   => '20:00:00',
		] );
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
}
