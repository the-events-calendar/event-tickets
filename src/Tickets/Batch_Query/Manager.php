<?php
/**
 * Batch Query Manager for optimizing multiple event queries.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Batch_Query
 */

namespace TEC\Tickets\Batch_Query;

use Tribe__Tickets__Tickets;

/**
 * Class Manager
 *
 * @since TBD
 *
 * @package TEC\Tickets\Batch_Query
 */
class Manager {

	/**
	 * Collection of event IDs to batch process.
	 *
	 * @since TBD
	 *
	 * @var array<int>
	 */
	private $event_ids = [];

	/**
	 * Preloaded ticket data by event ID.
	 *
	 * @since TBD
	 *
	 * @var array<int, array>
	 */
	private $preloaded_tickets = [];

	/**
	 * Preloaded ticket counts by event ID.
	 *
	 * @since TBD
	 *
	 * @var array<int, array>
	 */
	private $preloaded_counts = [];

	/**
	 * Preloaded attendee counts by event ID.
	 *
	 * @since TBD
	 *
	 * @var array<int, int>
	 */
	private $preloaded_attendee_counts = [];

	/**
	 * Whether preloading has been executed.
	 *
	 * @since TBD
	 *
	 * @var bool
	 */
	private $is_preloaded = false;

	/**
	 * Add an event ID to the batch.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID to add.
	 *
	 * @return void
	 */
	public function add_event( $event_id ) {
		if ( ! in_array( $event_id, $this->event_ids, true ) ) {
			$this->event_ids[] = (int) $event_id;
			$this->is_preloaded = false;
		}
	}

	/**
	 * Add multiple event IDs to the batch.
	 *
	 * @since TBD
	 *
	 * @param array<int> $event_ids Array of event IDs to add.
	 *
	 * @return void
	 */
	public function add_events( array $event_ids ) {
		foreach ( $event_ids as $event_id ) {
			$this->add_event( $event_id );
		}
	}

	/**
	 * Preload all data for the batch of events.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function preload() {
		if ( empty( $this->event_ids ) || $this->is_preloaded ) {
			return;
		}

		$this->preload_tickets();
		$this->preload_ticket_counts();
		$this->preload_attendee_counts();

		$this->is_preloaded = true;
	}

	/**
	 * Get ticket counts for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return array|null The ticket counts or null if not preloaded.
	 */
	public function get_ticket_counts( $event_id ) {
		if ( ! $this->is_preloaded ) {
			$this->preload();
		}

		return $this->preloaded_counts[ $event_id ] ?? null;
	}

	/**
	 * Get tickets for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return array|null The tickets or null if not preloaded.
	 */
	public function get_tickets( $event_id ) {
		if ( ! $this->is_preloaded ) {
			$this->preload();
		}

		return $this->preloaded_tickets[ $event_id ] ?? null;
	}

	/**
	 * Get attendee count for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int|null The attendee count or null if not preloaded.
	 */
	public function get_attendee_count( $event_id ) {
		if ( ! $this->is_preloaded ) {
			$this->preload();
		}

		return $this->preloaded_attendee_counts[ $event_id ] ?? null;
	}

	/**
	 * Preload tickets for all events in the batch.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function preload_tickets() {
		if ( empty( $this->event_ids ) ) {
			return;
		}

		// Use the ticket repository to fetch tickets for all events
		/** @var \Tribe__Tickets__Ticket_Repository $ticket_repository */
		$ticket_repository = tribe( 'tickets.ticket-repository' );
		
		// Get all tickets for the events in the batch
		$tickets = $ticket_repository
			->where( 'event__in', $this->event_ids )
			->order_by( 'event' )
			->order_by( 'menu_order', 'ASC' )
			->all();

		// Group tickets by event
		foreach ( $tickets as $ticket ) {
			if ( ! $ticket instanceof \Tribe__Tickets__Ticket_Object ) {
				continue;
			}
			
			$event_id = $ticket->get_event_id();
			if ( ! isset( $this->preloaded_tickets[ $event_id ] ) ) {
				$this->preloaded_tickets[ $event_id ] = [];
			}
			$this->preloaded_tickets[ $event_id ][] = $ticket;
		}

		// Ensure all events have an entry
		foreach ( $this->event_ids as $event_id ) {
			if ( ! isset( $this->preloaded_tickets[ $event_id ] ) ) {
				$this->preloaded_tickets[ $event_id ] = [];
			}
		}
	}

	/**
	 * Preload ticket counts for all events in the batch.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function preload_ticket_counts() {
		foreach ( $this->event_ids as $event_id ) {
			// Check cache first
			$counts = Tribe__Tickets__Tickets::get_ticket_counts( $event_id );
			$this->preloaded_counts[ $event_id ] = $counts;
		}
	}

	/**
	 * Preload attendee counts for all events in the batch.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function preload_attendee_counts() {
		if ( empty( $this->event_ids ) ) {
			return;
		}

		// Use the attendee repository to get counts for each event
		/** @var \Tribe__Tickets__Attendee_Repository $attendee_repository */
		$attendee_repository = tribe( 'tickets.attendee-repository' );

		// Get attendee counts for each event
		foreach ( $this->event_ids as $event_id ) {
			// Use the repository's count method which is optimized
			$count = $attendee_repository
				->where( 'event', $event_id )
				->where( 'order_status', [ 'completed', 'processing', 'publish' ] )
				->count();
			
			$this->preloaded_attendee_counts[ $event_id ] = $count;
		}
	}

	/**
	 * Clear the batch and reset preloaded data.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function clear() {
		$this->event_ids = [];
		$this->preloaded_tickets = [];
		$this->preloaded_counts = [];
		$this->preloaded_attendee_counts = [];
		$this->is_preloaded = false;
	}
}
