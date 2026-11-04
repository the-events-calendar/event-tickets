<?php
/**
 * Lists a date's own tickets in place of the templates of its recurring event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Events_Pro\Custom_Tables\V1\Events\Provisional\ID_Generator;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Date_Tickets.
 *
 * `get_tickets()` reads a date's tickets from its event, so on a date's page it finds the templates. Their place
 * goes to the date's published rows, read in one query, in menu order; every other ticket stays where it was.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Date_Tickets {
	/**
	 * The rows.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The hydrator.
	 *
	 * @since TBD
	 *
	 * @var Hydrator
	 */
	private Hydrator $hydrator;

	/**
	 * The template guard, which knows a template.
	 *
	 * @since TBD
	 *
	 * @var Template_Guard
	 */
	private Template_Guard $templates;

	/**
	 * Date_Tickets constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows           $rows      The rows.
	 * @param Hydrator       $hydrator  The hydrator.
	 * @param Template_Guard $templates The template guard.
	 */
	public function __construct( Rows $rows, Hydrator $hydrator, Template_Guard $templates ) {
		$this->rows      = $rows;
		$this->hydrator  = $hydrator;
		$this->templates = $templates;
	}

	/**
	 * Swaps the templates in a date's ticket list for the date's rows.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object[] $tickets The tickets of the post.
	 * @param int|string      $post_id The post ID as it was asked for.
	 *
	 * @return Ticket_Object[] The tickets, with the date's rows in place of the templates.
	 */
	public function swap( array $tickets, $post_id ): array {
		$occurrence_id = $this->occurrence_of( (int) $post_id );

		if ( ! $occurrence_id ) {
			return $tickets;
		}

		$template_ids = [];
		foreach ( $tickets as $ticket ) {
			if ( $ticket instanceof Ticket_Object && $this->templates->is_template( (int) $ticket->ID ) ) {
				$template_ids[] = (int) $ticket->ID;
			}
		}

		if ( ! $template_ids ) {
			return $tickets;
		}

		$rows = array_filter(
			$this->rows->get_by_occurrence( $occurrence_id ),
			static fn( Ticket $row ) => 'publish' === $row->status && in_array( (int) $row->parent_id, $template_ids, true )
		);
		$rows = array_map( [ $this->hydrator, 'hydrate' ], array_values( $rows ) );

		$swapped = [];
		foreach ( $tickets as $ticket ) {
			if ( ! in_array( (int) $ticket->ID, $template_ids, true ) ) {
				$swapped[] = $ticket;
				continue;
			}

			// The rows take the first template's place; the other templates go.
			array_push( $swapped, ...$rows );
			$rows = [];
		}

		return $swapped;
	}

	/**
	 * Returns the occurrence a date's post ID stands for.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return int|null The occurrence ID, or null when the ID is not a date or ECP provides no dates.
	 */
	private function occurrence_of( int $post_id ): ?int {
		if ( ! did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ) {
			return null;
		}

		$generator = tribe( ID_Generator::class );

		if ( $post_id <= $generator->current() || Ticket_ID::is_table_ticket( $post_id ) ) {
			return null;
		}

		return (int) $generator->unprovide_id( $post_id );
	}
}
