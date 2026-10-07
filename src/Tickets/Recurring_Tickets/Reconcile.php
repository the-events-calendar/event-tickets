<?php
/**
 * Recognises a date that ECP gave a new ID on the same day.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use DateTimeInterface;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events\Custom_Tables\V1\Tables\Occurrences;
use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Re-points the rows of a date that no longer exists to the event's new date on the same local calendar day.
 *
 * ECP gives every date a new ID when its start changes, so moving a class from 10:00 to 11:00 would strand every
 * ticket sold. Only an unambiguous day is re-pointed: one old date and one new date. Order items are not touched.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Reconcile {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * Reconcile constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Re-points the rows of an event's gone dates to its new dates on the same day, with their attendees.
	 *
	 * @since TBD
	 *
	 * @param int               $post_id The event's post ID.
	 * @param Ticket[]          $rows    The event's rows.
	 * @param array<int,object> $dates   The event's dates, by occurrence ID, with their local and UTC start.
	 *
	 * @return Ticket[] The event's rows, the re-pointed ones on their new date.
	 */
	public function repoint( int $post_id, array $rows, array $dates ): array {
		$moves = $this->moves( $rows, $dates );

		if ( ! $moves ) {
			return $rows;
		}

		$ids = tribe( ID_Generator::class );

		foreach ( $moves as $from => $to ) {
			$this->rows->repoint( $post_id, $from, $to, (string) $dates[ $to ]->start_date, (string) $dates[ $to ]->start_date_utc );
			$this->move_attendees( $rows, $from, (int) $ids->provide_id( $from ), (int) $ids->provide_id( $to ) );
		}

		$repointed = array_map( 'intval', array_keys( $moves ) );

		return array_merge(
			array_filter( $rows, static fn( Ticket $row ) => ! in_array( (int) $row->occurrence_id, $repointed, true ) ),
			array_filter( array_map( [ $this->rows, 'find' ], $this->row_ids_on( $rows, $repointed ) ) )
		);
	}

	/**
	 * Returns the gone dates to re-point, each to its new date.
	 *
	 * @since TBD
	 *
	 * @param Ticket[]          $rows  The event's rows.
	 * @param array<int,object> $dates The event's dates, by occurrence ID.
	 *
	 * @return array<int,int> The new occurrence ID of each gone one.
	 */
	private function moves( array $rows, array $dates ): array {
		$gone_by_day = [];
		$with_rows   = [];

		foreach ( $rows as $row ) {
			$occurrence_id               = (int) $row->occurrence_id;
			$with_rows[ $occurrence_id ] = true;

			if ( $occurrence_id && ! isset( $dates[ $occurrence_id ] ) ) {
				$gone_by_day[ $this->day( $row->occurrence_start ) ][ $occurrence_id ] = true;
			}
		}

		if ( ! $gone_by_day ) {
			return [];
		}

		// A date that moved to another event is not gone: it moves with its rows.
		$elsewhere = $this->existing( array_merge( ...array_map( 'array_keys', array_values( $gone_by_day ) ) ) );

		$new_by_day = [];
		foreach ( $dates as $occurrence_id => $date ) {
			if ( ! isset( $with_rows[ (int) $occurrence_id ] ) ) {
				$new_by_day[ $this->day( $date->start_date ) ][] = (int) $occurrence_id;
			}
		}

		$moves = [];
		foreach ( $gone_by_day as $day => $gone ) {
			$gone = array_diff( array_keys( $gone ), $elsewhere );

			if ( 1 === count( $gone ) && 1 === count( $new_by_day[ $day ] ?? [] ) ) {
				$moves[ (int) reset( $gone ) ] = $new_by_day[ $day ][0];
			}
		}

		return $moves;
	}

	/**
	 * Moves the attendees of a gone date's rows to the new date.
	 *
	 * @since TBD
	 *
	 * @param Ticket[] $rows     The event's rows.
	 * @param int      $from     The gone occurrence ID.
	 * @param int      $old_date The gone date's provisional ID.
	 * @param int      $new_date The new date's provisional ID.
	 *
	 * @return void
	 */
	private function move_attendees( array $rows, int $from, int $old_date, int $new_date ): void {
		foreach ( $this->row_ids_on( $rows, [ $from ] ) as $row_id ) {
			foreach ( tec_tc_attendees()->where( 'ticket_id', Ticket_ID::from_row_id( $row_id ) )->get_ids() as $attendee_id ) {
				update_post_meta( (int) $attendee_id, '_tec_tickets_commerce_event', $new_date, $old_date );
			}
		}
	}

	/**
	 * Returns the IDs of the rows on some dates.
	 *
	 * @since TBD
	 *
	 * @param Ticket[] $rows           The rows.
	 * @param int[]    $occurrence_ids The occurrence IDs.
	 *
	 * @return int[] The row IDs.
	 */
	private function row_ids_on( array $rows, array $occurrence_ids ): array {
		$ids = [];
		foreach ( $rows as $row ) {
			if ( in_array( (int) $row->occurrence_id, $occurrence_ids, true ) ) {
				$ids[] = (int) $row->id;
			}
		}

		return $ids;
	}

	/**
	 * Returns which of some occurrences still exist, on any event.
	 *
	 * @since TBD
	 *
	 * @param int[] $occurrence_ids The occurrence IDs.
	 *
	 * @return int[] The occurrence IDs that exist.
	 */
	private function existing( array $occurrence_ids ): array {
		$placeholders = implode( ', ', array_fill( 0, count( $occurrence_ids ), '%d' ) );

		return array_map(
			'intval',
			(array) DB::get_col(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One placeholder per ID.
				DB::prepare( "SELECT occurrence_id FROM %i WHERE occurrence_id IN ({$placeholders})", Occurrences::table_name(), ...$occurrence_ids )
			)
		);
	}

	/**
	 * Returns the local calendar day of a start.
	 *
	 * @since TBD
	 *
	 * @param mixed $start A start, as the model or the occurrences table holds it.
	 *
	 * @return string The day, `Y-m-d`.
	 */
	private function day( $start ): string {
		return substr( $start instanceof DateTimeInterface ? $start->format( 'Y-m-d H:i:s' ) : (string) $start, 0, 10 );
	}
}
