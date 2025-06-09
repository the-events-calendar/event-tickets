<?php
/**
 * Handles CRUD operations on Series Passes metadata.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repository;
 */

namespace TEC\Tickets\Flexible_Tickets\Series_Passes;

use Tribe__Cache_Listener as Cache_Listener;

/**
 * Class Metadata.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets\Repository;
 */
class Metadata {
	/**
	 * A reference to the Repository repository.
	 *
	 * @since 5.8.0
	 *
	 * @var Repository
	 */
	private Repository $repository;

	/**
	 * Metadata constructor.
	 *
	 * since 5.8.0
	 *
	 * @param Repository $repository A reference to the Repository repository.
	 */
	public function __construct( Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Returns a Series Pass ticket date metadata value.
	 *
	 * The method leverages the trigger-based caching system to store the ticket end date and time
	 * until an Event is created or updated. Series Passes depend on Series that depend on Events:
	 * it makes sense to pre-emptively invalidate the dynamic metadata when an Event is created or updated.
	 *
	 * @since 5.8.0
	 *
	 * @param int    $ticket_id The ID of the ticket.
	 * @param string $meta_key  The meta key to fetch.
	 *
	 * @return string The meta value, or an empty string if not found.
	 */
	private function get_ticket_date_metadata( int $ticket_id, string $meta_key ): string {
		$meta_value = get_post_meta( $ticket_id, $meta_key, true );

		if ( ! empty( $meta_value ) ) {
			return $meta_value;
		}

		// The following fetch happens on read and can be expensive, cache per-request.

		$cache             = tribe_cache();
		$cached_meta_value = $cache->get( 'pass_' . $ticket_id . $meta_key );

		if ( ! empty( $cached_meta_value ) ) {
			return $cached_meta_value;
		}

		$last = $this->repository->get_last_occurrence_by_ticket( $ticket_id );

		if ( ! $last instanceof \WP_Post ) {
			return '';
		}

		$ticket_end_date = $last->dates->start->format( 'Y-m-d' );
		$ticket_end_time = $last->dates->start->format( 'H:i:s' );
		// Set both even if only one is requested, to avoid fetching the same data twice.
		$cache->set( 'pass_' . $ticket_id . '_ticket_end_date', $ticket_end_date, 0, Cache_Listener::TRIGGER_SAVE_POST );
		$cache->set( 'pass_' . $ticket_id . '_ticket_end_time', $ticket_end_time, 0, Cache_Listener::TRIGGER_SAVE_POST );

		return $meta_key === '_ticket_end_date' ? $ticket_end_date : $ticket_end_time;
	}

	/**
	 * Returns the ticket end time for a Series Pass.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The ID of the ticket.
	 *
	 * @return string The ticket end time.
	 */
	public function get_ticket_end_time( int $ticket_id ): string {
		return $this->get_ticket_date_metadata( $ticket_id, '_ticket_end_time' );
	}

	/**
	 * Returns a Series Pass ticket end date metadata value.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The ID of the ticket.
	 *
	 * @return string The meta value, or an empty string if not found.
	 */
	public function get_ticket_end_date( int $ticket_id ): string {
		return $this->get_ticket_date_metadata( $ticket_id, '_ticket_end_date' );
	}
}
