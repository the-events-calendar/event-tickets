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
use WP_Query;

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
		global $wpdb;

		if ( empty( $this->event_ids ) ) {
			return;
		}

		// Get all tickets for all events in one query
		$placeholders = implode( ',', array_fill( 0, count( $this->event_ids ), '%d' ) );
		
		$query = $wpdb->prepare(
			"SELECT p.ID, p.post_parent, p.post_title, p.post_status, p.menu_order,
				pm1.meta_value as _price,
				pm2.meta_value as _capacity,
				pm3.meta_value as _ticket_start_date,
				pm4.meta_value as _ticket_end_date,
				pm5.meta_value as _tribe_ticket_provider
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm1 ON (p.ID = pm1.post_id AND pm1.meta_key = '_price')
			LEFT JOIN {$wpdb->postmeta} pm2 ON (p.ID = pm2.post_id AND pm2.meta_key = '_capacity')
			LEFT JOIN {$wpdb->postmeta} pm3 ON (p.ID = pm3.post_id AND pm3.meta_key = '_ticket_start_date')
			LEFT JOIN {$wpdb->postmeta} pm4 ON (p.ID = pm4.post_id AND pm4.meta_key = '_ticket_end_date')
			LEFT JOIN {$wpdb->postmeta} pm5 ON (p.ID = pm5.post_id AND pm5.meta_key = '_tribe_ticket_provider')
			WHERE p.post_parent IN ({$placeholders})
			AND p.post_type IN ('tribe_tpp_tickets', 'tribe_rsvp_tickets', 'product', 'download')
			AND p.post_status IN ('publish', 'draft', 'private')
			ORDER BY p.post_parent, p.menu_order ASC",
			...$this->event_ids
		);

		$results = $wpdb->get_results( $query );

		// Group tickets by event
		foreach ( $results as $ticket ) {
			$event_id = $ticket->post_parent;
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
		global $wpdb;

		if ( empty( $this->event_ids ) ) {
			return;
		}

		$placeholders = implode( ',', array_fill( 0, count( $this->event_ids ), '%d' ) );
		
		// Count attendees for all events in one query
		$query = $wpdb->prepare(
			"SELECT pm.meta_value as event_id, COUNT(DISTINCT p.ID) as attendee_count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
			WHERE pm.meta_key = '_tribe_tickets_event_id'
			AND pm.meta_value IN ({$placeholders})
			AND p.post_type IN ('tribe_rsvp_attendees', 'tribe_tpp_attendees', 'shop_order', 'tribe_attendee')
			AND p.post_status NOT IN ('trash', 'auto-draft')
			GROUP BY pm.meta_value",
			...$this->event_ids
		);

		$results = $wpdb->get_results( $query );

		// Store counts
		foreach ( $results as $result ) {
			$this->preloaded_attendee_counts[ (int) $result->event_id ] = (int) $result->attendee_count;
		}

		// Ensure all events have an entry
		foreach ( $this->event_ids as $event_id ) {
			if ( ! isset( $this->preloaded_attendee_counts[ $event_id ] ) ) {
				$this->preloaded_attendee_counts[ $event_id ] = 0;
			}
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