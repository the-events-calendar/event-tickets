<?php
/**
 * Moves rows with the dates ECP moves to another event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Keeps a date's rows with the date when ECP moves it to another event: "This and following" splits the event and
 * moves the later dates to a copy of it.
 *
 * ECP duplicates the event first, and ET clones its tickets onto the copy: the rows move onto those clones of their
 * templates, with their sales and stock. The dates keep their IDs, so the attendees keep their date's ID.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Detach {
	/**
	 * The clone of each ticket, by original and copy event, from the duplications of this request.
	 *
	 * @since TBD
	 *
	 * @var array<string,array<int,int>>
	 */
	private array $clones = [];

	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * Detach constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Remembers which ticket a duplication cloned into which.
	 *
	 * @since TBD
	 *
	 * @param array<int,int> $map              The clone of each ticket, by ticket ID.
	 * @param int            $new_post_id      The copy of the event.
	 * @param int            $original_post_id The event.
	 *
	 * @return void
	 */
	public function remember_clones( $map, $new_post_id, $original_post_id ): void {
		foreach ( (array) $map as $ticket_id => $clone_id ) {
			if ( $clone_id ) {
				$this->clones[ $this->pair( (int) $original_post_id, (int) $new_post_id ) ][ (int) $ticket_id ] = (int) $clone_id;
			}
		}
	}

	/**
	 * Moves the rows of dates moved to another event onto that event's clones of their templates.
	 *
	 * @since TBD
	 *
	 * @param int   $from_id        The event the dates left.
	 * @param int   $to_id          The event they belong to now.
	 * @param int[] $occurrence_ids The moved dates.
	 *
	 * @return void
	 */
	public function move_rows( $from_id, $to_id, $occurrence_ids ): void {
		$from_id        = (int) $from_id;
		$to_id          = (int) $to_id;
		$occurrence_ids = array_map( 'intval', (array) $occurrence_ids );

		$by_template = [];
		foreach ( $this->rows->get_by_post( $from_id ) as $row ) {
			if ( in_array( (int) $row->occurrence_id, $occurrence_ids, true ) ) {
				$by_template[ (int) $row->parent_id ][] = (int) $row->id;
			}
		}

		foreach ( $by_template as $template_id => $row_ids ) {
			$clone_id = $this->clone_of( $from_id, $to_id, $template_id );

			if ( ! $clone_id ) {
				continue;
			}

			// A clone saved after the move got fresh rows for the moved dates from Sync: the moving rows replace them.
			$fresh = array_filter(
				$this->rows->get_by_template( $clone_id ),
				static fn( Ticket $row ) => in_array( (int) $row->occurrence_id, $occurrence_ids, true ) && 0 === (int) $row->sales
			);
			$this->rows->delete_ids( array_map( static fn( Ticket $row ) => (int) $row->id, $fresh ) );

			$this->rows->move( $row_ids, $to_id, $clone_id );
		}
	}

	/**
	 * Returns the copy event's clone of a template, cloning it when the duplication left it out.
	 *
	 * @since TBD
	 *
	 * @param int $from_id     The event.
	 * @param int $to_id       The copy event.
	 * @param int $template_id The template.
	 *
	 * @return int The clone's ID, 0 when it could not be made.
	 */
	private function clone_of( int $from_id, int $to_id, int $template_id ): int {
		$pair = $this->pair( $from_id, $to_id );

		if ( empty( $this->clones[ $pair ][ $template_id ] ) ) {
			$this->clones[ $pair ][ $template_id ] = (int) tribe( Module::class )->clone_ticket_to_new_post( $from_id, $to_id, $template_id );
		}

		return $this->clones[ $pair ][ $template_id ];
	}

	/**
	 * Returns the key of an event and its copy.
	 *
	 * @since TBD
	 *
	 * @param int $from_id The event.
	 * @param int $to_id   The copy.
	 *
	 * @return string The key.
	 */
	private function pair( int $from_id, int $to_id ): string {
		return "{$from_id}:{$to_id}";
	}
}
