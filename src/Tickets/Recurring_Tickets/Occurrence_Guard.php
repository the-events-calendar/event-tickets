<?php
/**
 * Keeps occurrence ID normalization from turning a table ticket ID into a date's event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

/**
 * Class Occurrence_Guard.
 *
 * ECP reads any ID above its provisional base as a date, so a table ticket ID would be looked up as an occurrence.
 * The ID is recorded before ECP's callback and handed back after every callback, whatever they made of it.
 * ECP's one lookup still runs: a filter cannot stop a later callback.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Occurrence_Guard {
	/**
	 * The filter that normalizes occurrence IDs.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const FILTER = 'tec_events_custom_tables_v1_normalize_occurrence_id';

	/**
	 * The table ticket ID of each normalization in progress, null for any other ID. A stack, for nested calls.
	 *
	 * @since TBD
	 *
	 * @var array<int,int|null>
	 */
	private array $in_progress = [];

	/**
	 * Records the ID a normalization starts with.
	 *
	 * @since TBD
	 *
	 * @param mixed $id The ID being normalized.
	 *
	 * @return mixed The ID, unchanged.
	 */
	public function remember( $id ) {
		$this->in_progress[] = Ticket_ID::is_table_ticket( $id ) ? (int) $id : null;

		return $id;
	}

	/**
	 * Hands back a table ticket ID the normalization started with.
	 *
	 * @since TBD
	 *
	 * @param mixed $id The normalized ID.
	 *
	 * @return mixed The table ticket ID the normalization started with, otherwise the normalized ID.
	 */
	public function restore( $id ) {
		$original = array_pop( $this->in_progress );

		return $original ?? $id;
	}
}
