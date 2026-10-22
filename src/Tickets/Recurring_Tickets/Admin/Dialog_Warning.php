<?php
/**
 * The warning, in ECP's save and delete dialog, about upcoming dates with sold tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets as Tickets_Table;

/**
 * Warns, before a change that can remove dates, how many upcoming dates have sold tickets (EngDoc section D5).
 *
 * It warns; it never blocks the change.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Dialog_Warning {
	/**
	 * Adds the warning to the dialog's notices when upcoming dates of the event have sold tickets.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $data    The dialog's strings and its notices.
	 * @param int                       $post_id The event being edited, 0 if none.
	 *
	 * @return array<string,mixed> The dialog's data.
	 */
	public function add_notice( $data, $post_id ): array {
		$data  = (array) $data;
		$count = $post_id ? $this->count_dates_with_sales( (int) $post_id ) : 0;

		if ( ! $count ) {
			return $data;
		}

		$data['notices']   = (array) ( $data['notices'] ?? [] );
		$data['notices'][] = [
			'text'        => sprintf(
				// translators: %d is the number of upcoming dates of the event with sold tickets.
				_n(
					'%d upcoming date has sold tickets. Dates that are removed, or can\'t be matched to a new date, will leave their attendees stranded.',
					'%d upcoming dates have sold tickets. Dates that are removed, or can\'t be matched to a new date, will leave their attendees stranded.',
					$count,
					'event-tickets'
				),
				$count
			),
			'context'     => 'any',
			'destructive' => true,
		];

		return $data;
	}

	/**
	 * Returns how many upcoming dates of an event have sold tickets.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The event.
	 *
	 * @return int The number of dates.
	 */
	private function count_dates_with_sales( int $post_id ): int {
		return (int) DB::get_var(
			DB::prepare(
				'SELECT COUNT( DISTINCT occurrence_id ) FROM %i WHERE post_id = %d AND sales > 0 AND occurrence_start_utc >= %s',
				Tickets_Table::table_name(),
				$post_id,
				gmdate( 'Y-m-d H:i:s' )
			)
		);
	}
}
