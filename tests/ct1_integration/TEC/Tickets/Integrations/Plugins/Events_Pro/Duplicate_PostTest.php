<?php

namespace TEC\Tickets\Integrations\Plugins\Events_Pro;

use Codeception\TestCase\WPTestCase;
use Generator;
use Closure;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use TEC\Events_Pro\Custom_Tables\V1\Duplicate\Duplicate;
use Tribe\Tickets\Events\Attendees_List;
use Tribe__Tickets__Global_Stock as Global_Stock;
use WP_Post;
use Tribe__Tickets__Tickets;

class Duplicate_PostTest extends WPTestCase {
	use Ticket_Maker;

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
					tribe( 'tickets.handler' )->key_capacity       => 100,
					tribe( 'tickets.handler' )->key_image_header   => 4,
					tribe( 'tickets.handler' )->key_provider_field => 'TEC\Tickets\Commerce\Module',
					Global_Stock::GLOBAL_STOCK_ENABLED             => true,
					Global_Stock::GLOBAL_STOCK_LEVEL               => 9,
					Attendees_List::HIDE_META_KEY                  => false,
				];

				foreach( $meta as $k => $v ) {
					update_post_meta( $event_id, $k, $v );
				}

				return [ $event_id, [ $ticket_id_1, $ticket_id_2 ], $meta ];
			}
		];
	}

	/**
	 * @test
	 * @dataProvider event_with_tickets_provider
	 * it should duplicate tickets along with event
	 */
	public function it_should_duplicate_tickets_along_with_event( Closure $fixture ) {
		[ $event_id, $ticket_ids, $meta ] = $fixture();

		$duplicator = tribe( Duplicate::class );

		$event = get_post( $event_id );

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
			$val_to_check = $k !== Global_Stock::GLOBAL_STOCK_LEVEL ? $v : $meta[ tribe( 'tickets.handler' )->key_capacity ];
			$this->assertEquals( $val_to_check, get_post_meta( $duplicated_event->ID, $k, true ), $k );
		}
	}

	/**
	 * @test
	 * it should duplicate shared-capacity tickets at full capacity
	 */
	public function it_should_duplicate_shared_capacity_tickets_at_full_capacity() {
		$capacity = 100;

		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Shared Capacity Event',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		$shared = [ 'tribe-ticket' => [ 'mode' => Global_Stock::GLOBAL_STOCK_MODE, 'capacity' => $capacity, 'event_capacity' => $capacity ] ];
		$this->create_tc_ticket( $event_id, 10, $shared );
		$this->create_tc_ticket( $event_id, 10, $shared );

		/*
		 * Pin the source event into a shared-capacity state, then simulate prior sales so the
		 * pool sits below capacity — the case where naively copying the level would be wrong.
		 */
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, true );
		update_post_meta( $event_id, tribe( 'tickets.handler' )->key_capacity, $capacity );
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, $capacity - 20 );

		$duplicated_event = tribe( Duplicate::class )->duplicate_event( get_post( $event_id ) );

		$this->assertInstanceOf( WP_Post::class, $duplicated_event );

		/* A fresh duplicate has no sales: capacity and pool are both the full capacity. */
		$this->assertEquals( $capacity, get_post_meta( $duplicated_event->ID, tribe( 'tickets.handler' )->key_capacity, true ), 'event capacity' );
		$this->assertEquals( $capacity, get_post_meta( $duplicated_event->ID, Global_Stock::GLOBAL_STOCK_LEVEL, true ), 'event stock level' );

		foreach ( Tribe__Tickets__Tickets::get_all_event_tickets( $duplicated_event->ID ) as $ticket ) {
			$this->assertEquals( $capacity, $ticket->capacity(), 'duplicated ticket capacity' );
		}
	}
}
