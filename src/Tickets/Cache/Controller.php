<?php
/**
 * Controller for the ticket caching functionality.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cache
 */

namespace TEC\Tickets\Cache;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets__Tickets;
use Tribe__Tickets__Ticket_Object;

/**
 * Class Controller
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cache
 */
class Controller extends Controller_Contract {

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->add_actions();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 */
	public function unregister(): void {
		$this->remove_actions();
	}

	/**
	 * Add the actions required by the caching system.
	 *
	 * @since TBD
	 */
	protected function add_actions() {
		add_action( 'init', [ $this, 'register_cache_invalidation_hooks' ] );
	}

	/**
	 * Remove the actions.
	 *
	 * @since TBD
	 */
	protected function remove_actions() {
		remove_action( 'init', [ $this, 'register_cache_invalidation_hooks' ] );
	}

	/**
	 * Register hooks for cache invalidation.
	 *
	 * @since TBD
	 */
	public function register_cache_invalidation_hooks() {
		// Clear cache when tickets are saved or deleted.
		add_action( 'event_tickets_after_save_ticket', [ $this, 'clear_cache_for_ticket' ], 10, 3 );
		add_action( 'event_tickets_attendee_ticket_deleted', [ $this, 'clear_cache_for_ticket' ], 10, 2 );

		// Clear cache when attendees are created, updated, or deleted.
		add_action( 'tec_tickets_commerce_attendee_after_create', [ $this, 'clear_cache_for_attendee' ] );
		add_action( 'tec_tickets_commerce_attendee_after_update', [ $this, 'clear_cache_for_attendee' ] );
		add_action( 'tec_tickets_commerce_attendee_after_delete', [ $this, 'clear_cache_for_attendee' ] );

		// Clear cache for RSVP operations.
		add_action( 'event_tickets_rsvp_tickets_generated', [ $this, 'clear_cache_for_ticket' ], 10, 2 );

		// Clear cache when check-in status changes.
		add_action( 'event_tickets_checkin', [ $this, 'clear_cache_for_attendee' ] );
		add_action( 'event_tickets_uncheckin', [ $this, 'clear_cache_for_attendee' ] );

		// Clear cache when stock changes.
		add_action( 'tec_tickets_ticket_stock_changed', [ $this, 'clear_cache_for_ticket' ], 10, 2 );

		// Clear Views V2 tickets cache when counts are cleared.
		add_action( 'tec_tickets_cache_clear_ticket_counts', [ $this, 'clear_views_v2_cache' ] );
	}

	/**
	 * Clear cache for a specific ticket's event.
	 *
	 * @since TBD
	 *
	 * @param int                           $ticket_id The ticket ID.
	 * @param int                           $event_id  The event ID.
	 * @param Tribe__Tickets__Ticket_Object $ticket    The ticket object (optional).
	 */
	public function clear_cache_for_ticket( $ticket_id, $event_id = null, $ticket = null ) {
		// Handle RSVP case where first param might be order_id and second is event_id.
		if ( ! empty( $event_id ) && is_numeric( $event_id ) ) {
			// Direct event ID provided.
			Tribe__Tickets__Tickets::clear_ticket_counts_cache( $event_id );
			$this->clear_views_v2_cache( $event_id );
			return;
		}

		// Try to get event ID from ticket object.
		if ( ! $event_id && $ticket instanceof Tribe__Tickets__Ticket_Object ) {
			$event_id = $ticket->get_event_id();
		}

		// Try to get event ID from ticket ID.
		if ( ! $event_id && is_numeric( $ticket_id ) ) {
			$ticket_post = get_post( $ticket_id );
			if ( $ticket_post ) {
				// For RSVP and other ticket types, get the parent event.
				$event_id = get_post_meta( $ticket_id, '_tribe_rsvp_for_event', true );
				if ( ! $event_id ) {
					$event_id = get_post_meta( $ticket_id, '_tribe_wooticket_for_event', true );
				}
				if ( ! $event_id ) {
					$event_id = get_post_meta( $ticket_id, '_tribe_eddticket_for_event', true );
				}
			}
		}

		if ( $event_id ) {
			Tribe__Tickets__Tickets::clear_ticket_counts_cache( $event_id );
			$this->clear_views_v2_cache( $event_id );
		}
	}

	/**
	 * Clear cache for an attendee's event.
	 *
	 * @since TBD
	 *
	 * @param mixed $attendee The attendee object or ID.
	 */
	public function clear_cache_for_attendee( $attendee ) {
		$event_id = null;

		if ( is_object( $attendee ) && isset( $attendee->event_id ) ) {
			$event_id = $attendee->event_id;
		} elseif ( is_numeric( $attendee ) ) {
			// Get event ID from attendee.
			$attendee_event = get_post_meta( $attendee, '_tribe_tickets_event_id', true );
			if ( $attendee_event ) {
				$event_id = $attendee_event;
			}
		}

		if ( $event_id ) {
			Tribe__Tickets__Tickets::clear_ticket_counts_cache( $event_id );
			$this->clear_views_v2_cache( $event_id );
		}
	}

	/**
	 * Clear the Views V2 tickets cache for a specific event.
	 *
	 * @since TBD
	 *
	 * @param int|Tribe__Tickets__Ticket_Object $event_id The event ID or ticket object.
	 */
	public function clear_views_v2_cache( $event_id ) {
		// Handle ticket object case.
		if ( $event_id instanceof Tribe__Tickets__Ticket_Object ) {
			$event_id = $event_id->get_event_id();
		}

		if ( empty( $event_id ) || ! is_numeric( $event_id ) ) {
			return;
		}

		$cache_key = 'tribe_tickets_v2_data_' . $event_id;
		$cache     = tribe_cache();
		$cache->delete_transient( $cache_key );
	}
}
