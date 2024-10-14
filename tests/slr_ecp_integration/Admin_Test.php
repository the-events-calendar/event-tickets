<?php

namespace TEC\Tickets\Seating\Admin;

use TEC\Common\Tests\Provider\Controller_Test_Case;
use TEC\Tickets\Seating\Admin;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Events_Pro\Custom_Tables\V1\Duplicate\Duplicate;
use Tribe\Tickets\Events\Attendees_List;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Generator;
use Closure;
use TEC\Tickets\Seating\Meta;
use WP_Post;

class Admin_Test extends Controller_Test_Case {
	use Ticket_Maker;

	protected string $controller_class = Admin::class;

	public function event_with_tickets_provider(): Generator {
		yield 'event with tickets' => [
			function () {
				$event_id = tribe_events()->set_args(
					[
						'title'      => 'Test Event',
						'status'     => 'publish',
						'start_date' => '2020-01-01 12:00:00',
						'duration'   => 2 * HOUR_IN_SECONDS,
					]
				)->create()->ID;

				$ticket_id_1 = $this->create_tc_ticket( $event_id );
				$ticket_id_2 = $this->create_tc_ticket( $event_id );

				wp_update_post(
					[
						'ID'           => $event_id,
						'post_content' => '"ticketId":' . $ticket_id_1 . ',"ticketId":' . $ticket_id_2,
					]
				);

				$meta = [
					Meta::META_KEY_ENABLED   => 1,
					Meta::META_KEY_LAYOUT_ID => 'layout-uuid-1',
				];

				$ticket_meta = [
					$ticket_id_1 => [
						Meta::META_KEY_ENABLED   => 1,
						Meta::META_KEY_SEAT_TYPE => 'seattype-uuid-1',
					],
					$ticket_id_2 => [
						Meta::META_KEY_ENABLED   => 1,
						Meta::META_KEY_SEAT_TYPE => 'seattype-uuid-2',
					],
				];

				update_post_meta( $event_id, META::META_KEY_UUID, 'event-uuid-1' );

				foreach( $meta as $k => $v ) {
					update_post_meta( $event_id, $k, $v );
				}

				foreach ( $ticket_meta as $ticket_id => $data ) {
					foreach( $data as $k => $v ) {
						update_post_meta( $ticket_id, $k, $v );
					}
				}

				return [ $event_id, [ $ticket_id_1, $ticket_id_2 ], $meta, $ticket_meta ];
			}
		];
	}

	/**
	 * @test
	 * @dataProvider event_with_tickets_provider
	 * it should duplicate tickets along with event
	 */
	public function it_should_duplicate_tickets_along_with_event( Closure $fixture ) {
		[ $event_id, $ticket_ids, $meta, $tickets_meta ] = $fixture();

		$duplicator = tribe( Duplicate::class );

		$event = get_post( $event_id );

		add_action( 'tec_tickets_tickets_duplicated', function( $tickets_map ) use ( $tickets_meta ) {
			foreach ( $tickets_map as $old_ticket_id => $new_ticket_id ) {
				foreach( $tickets_meta[ $old_ticket_id ] as $k => $v ) {
					$this->assertEquals( $v, get_post_meta( $old_ticket_id, $k, true ), $k );
					$this->assertEquals( $v, get_post_meta( $new_ticket_id, $k, true ), $k );
				}
			}
		} );

		$duplicated_event = $duplicator->duplicate_event( $event );

		$this->assertInstanceOf( WP_Post::class, $duplicated_event );

		$this->assertEquals( count( $ticket_ids ), did_action( 'tec_tickets_ticket_duplicated' ) );
		$this->assertEquals( 1, did_action( 'tec_tickets_tickets_duplicated' ) );

		// refresh.
		$duplicated_event = get_post( $duplicated_event->ID );
		foreach ( $ticket_ids as $ticket_id ) {
			$this->assertContains( (string) $ticket_id, $event->post_content );
			$this->assertNotContains( (string) $ticket_id, $duplicated_event->post_content );
		}

		foreach( $meta as $k => $v ) {
			$this->assertEquals( $v, get_post_meta( $event_id, $k, true ), $k );
			$this->assertEquals( $v, get_post_meta( $duplicated_event->ID, $k, true ), $k );
		}
	}
}
