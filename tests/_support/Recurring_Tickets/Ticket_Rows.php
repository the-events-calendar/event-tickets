<?php
/**
 * Creates recurring events and rows of the Recurring Event Tickets table in tests.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */

namespace TEC\Tickets\Tests\Recurring_Tickets;

use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;

/**
 * Trait Ticket_Rows.
 *
 * @package TEC\Tickets\Tests\Recurring_Tickets
 */
trait Ticket_Rows {
	/**
	 * Creates a recurring event with one date a day, starting tomorrow.
	 *
	 * @param int $dates How many dates.
	 *
	 * @return int The event post ID.
	 */
	protected function create_recurring_event( int $dates = 3 ): int {
		$start = gmdate( 'Y-m-d', strtotime( '+1 day' ) );

		return tribe_events()->set_args(
			[
				'title'      => 'Recurring Event',
				'status'     => 'publish',
				'start_date' => "{$start} 10:00:00",
				'end_date'   => "{$start} 12:00:00",
				'recurrence' => "RRULE:FREQ=DAILY;COUNT={$dates}",
			]
		)->create()->ID;
	}

	/**
	 * Returns the dates of an event, in start order.
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return Occurrence[] The dates.
	 */
	protected function get_dates( int $post_id ): array {
		return iterator_to_array( Occurrence::where( 'post_id', $post_id )->order_by( 'start_date', 'ASC' )->all(), false );
	}

	/**
	 * Inserts a row and returns its ticket ID.
	 *
	 * @param array<string,mixed> $values Column values to replace the defaults with.
	 *
	 * @return int The ticket ID.
	 */
	protected function insert_ticket_row( array $values = [] ): int {
		Tickets::insert(
			array_merge(
				[
					'type'       => 'recurring',
					'post_id'    => 1,
					'name'       => 'General Admission',
					'price'      => 1050,
					'capacity'   => 100,
					'stock'      => 100,
					'sales'      => 0,
					'menu_order' => 0,
					'created_at' => '2026-10-01 00:00:00',
				],
				$values
			)
		);

		return Ticket_ID::from_row_id( (int) DB::last_insert_id() );
	}
}
