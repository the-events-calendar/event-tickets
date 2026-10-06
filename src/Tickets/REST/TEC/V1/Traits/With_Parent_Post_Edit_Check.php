<?php
/**
 * Trait With_Parent_Post_Edit_Check
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets\REST\TEC\V1\Traits;

use Tribe__Tickets__Tickets as Tickets;
use WP_REST_Request;

/**
 * Requires `edit_post` on the event a ticket belongs to, on top of the base ticket permission.
 *
 * A ticket is registered with `capability_type => 'post'`, so holding `create_posts` or
 * `edit_post` on the ticket is not enough on its own: writing a ticket also writes to the
 * event it points at (its stock and capacity). Both create and update therefore additionally
 * require that the current user can edit that event. SVUL-133.
 *
 * @since 5.30.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_Parent_Post_Edit_Check {
	/**
	 * A ticket may only be created on an event the current user can edit.
	 *
	 * @since 5.30.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool
	 */
	public function can_create( WP_REST_Request $request ): bool {
		if ( ! parent::can_create( $request ) ) {
			return false;
		}

		return $this->current_user_can_edit_parent_event( (int) $request->get_param( 'event' ) );
	}

	/**
	 * A ticket may only be updated by a user who can edit the event it belongs to.
	 *
	 * @since 5.30.0
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return bool
	 */
	public function can_update( WP_REST_Request $request ): bool {
		if ( ! parent::can_update( $request ) ) {
			return false;
		}

		$id = (int) ( $request['id'] ?? 0 );

		// The parent check above already authorized editing this ticket, and rejects a missing or
		// non-editable one. Without an id there is no ticket and so no event to gate on, so the
		// parent's decision stands. The update route always carries an id, so this is only defensive.
		if ( ! $id ) {
			return true;
		}

		// Resolve the event through the provider so it is correct for both Tickets Commerce and WooCommerce tickets.
		$ticket = Tickets::load_ticket_object( $id );
		$event  = $ticket ? (int) $ticket->get_event_id() : 0;

		return $this->current_user_can_edit_parent_event( $event );
	}

	/**
	 * Whether the current user can edit the given parent event.
	 *
	 * @since 5.30.0
	 *
	 * @param int $event The event ID the ticket belongs to.
	 *
	 * @return bool
	 */
	private function current_user_can_edit_parent_event( int $event ): bool {
		// A missing event is a 400 from the handler, not a permission failure.
		if ( ! $event ) {
			return true;
		}

		return current_user_can( 'edit_post', $event );
	}
}
