<?php

namespace TEC\Tickets\Commerce\Stock\SharedCapacity;

use TEC\Tickets\Commerce\Attendee;
use TEC\Tickets\Commerce\Module;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Order_Maker;
use Tribe\Tickets\Test\Commerce\TicketsCommerce\Ticket_Maker;
use Tribe__Events__Main as TEC;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Guards the shared capacity inventory calculation against the N+1 attendee fetch.
 *
 * @since TBD
 */
class Shared_Capacity_Inventory_Performance_Test extends \Codeception\TestCase\WPTestCase {

	use Ticket_Maker;
	use Order_Maker;

	/**
	 * The attendee queries captured during a measurement.
	 *
	 * @var array<string>
	 */
	private array $captured_queries = [];

	/**
	 * @before
	 */
	public function ensure_post_ticketable(): void {
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		$ticketable[] = TEC::POSTTYPE;
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );
	}

	public function test_should_not_make_n_plus_one_attendee_queries_when_calculating_shared_capacity_inventory(): void {
		[ $event_id, $ticket_ids ] = $this->given_an_event_with_shared_capacity_tickets( 4 );

		$tickets = array_map(
			static fn( $ticket_id ) => tribe( Module::class )->get_ticket( $event_id, $ticket_id ),
			$ticket_ids
		);

		$this->given_cold_caches();

		// First pass: load attendees from the database (cold cache).
		$this->when_measuring_attendee_queries(
			static function () use ( $tickets ) {
				foreach ( $tickets as $ticket ) {
					$ticket->inventory();
				}
			}
		);

		// Second pass: all attendee and inventory caches should be warm.
		$warm_queries = $this->when_measuring_attendee_queries(
			static function () use ( $tickets ) {
				foreach ( $tickets as $ticket ) {
					$ticket->inventory();
				}
			}
		);

		$this->assertCount(
			0,
			$warm_queries,
			'After the first pass populates the caches, subsequent inventory() calls should make zero attendee queries.'
		);
	}

	/**
	 * Creates an Event with global stock enabled and $count shared capacity tickets, one attendee each.
	 *
	 * @return array{0: int, 1: array<int>} The Event ID and the ticket IDs.
	 */
	private function given_an_event_with_shared_capacity_tickets( int $count ): array {
		$event_id = tribe_events()->set_args(
			[
				'title'      => 'Shared capacity inventory performance',
				'status'     => 'publish',
				'start_date' => '2020-01-01 12:00:00',
				'duration'   => 2 * HOUR_IN_SECONDS,
			]
		)->create()->ID;

		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_ENABLED, 1 );
		update_post_meta( $event_id, Global_Stock::GLOBAL_STOCK_LEVEL, 100 );

		$ticket_ids = [];

		for ( $i = 0; $i < $count; $i++ ) {
			$ticket_ids[] = $this->create_tc_ticket(
				$event_id,
				10 + $i,
				[
					'tribe-ticket' => [
						'mode'     => 0 === $i % 2 ? Global_Stock::GLOBAL_STOCK_MODE : Global_Stock::CAPPED_STOCK_MODE,
						'capacity' => 20,
					],
				]
			);
		}

		// One attendee per ticket, so the event wide attendee loop has something to walk.
		$this->create_order( array_fill_keys( $ticket_ids, 1 ) );

		return [ $event_id, $ticket_ids ];
	}

	/**
	 * Drops every cache layer so the measured calls start cold.
	 */
	private function given_cold_caches(): void {
		tribe( 'cache' )->reset();
		wp_cache_flush();
	}

	/**
	 * Runs the callback while capturing the SQL queries that read the Attendee post type.
	 *
	 * @return array<string> The captured queries.
	 */
	private function when_measuring_attendee_queries( callable $callback ): array {
		$this->captured_queries = [];

		$spy = function ( $query ) {
			if ( false !== strpos( (string) $query, Attendee::POSTTYPE ) ) {
				$this->captured_queries[] = $query;
			}

			return $query;
		};

		add_filter( 'query', $spy );
		$callback();
		remove_filter( 'query', $spy );

		return $this->captured_queries;
	}
}
