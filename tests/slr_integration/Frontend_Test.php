<?php

namespace TEC\Tickets\Seating\Frontend;

use tad\Codeception\SnapshotAssertions\SnapshotAssertions;
use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Commerce\Tickets_View;
use TEC\Tickets\Flexible_Tickets\Test\Traits\Series_Pass_Factory;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets\Seating\Meta;
use TEC\Tickets\Seating\Service\Service;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;

class Frontend_Test extends Controller_Test_Case {
	use SnapshotAssertions;
	use Ticket_Maker;
	use Series_Pass_Factory;

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

	public function seating_enabled_fixtures(): \Generator {
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

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'yes' );

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

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'yes' );

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

				update_post_meta( $post_id, Meta::META_KEY_LAYOUT_ID, 'yes' );

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
	public function should_replace_ticket_block_when_seating_is_enabled( \Closure $fixture ) {
		$this->test_services->singleton( Service::class, function () {
			return $this->make( Service::class, [
				'frontend_base_url'   => 'https://service.test.local',
				'get_ephemeral_token' => 'test-ephemeral-token',
				'get_post_uuid' => 'test-post-uuid',
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
}
