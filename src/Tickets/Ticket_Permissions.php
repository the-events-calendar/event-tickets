<?php
/**
 * Who may edit or delete a ticket.
 *
 * @since TBD
 *
 * @package TEC\Tickets
 */

namespace TEC\Tickets;

use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;

/**
 * Class Ticket_Permissions.
 *
 * Answers whether a user may edit or delete a ticket. Whoever may edit the ticket's post may edit
 * its tickets, and delete those they may also delete as posts; each answer passes through its own
 * filter, last.
 *
 * @since TBD
 *
 * @package TEC\Tickets
 */
final class Ticket_Permissions {
	/**
	 * Whether a user may edit the tickets of a post: whether they may edit the post.
	 *
	 * @since TBD
	 *
	 * @param int      $post_id The ID of the post. An occurrence ID is normalized to its event.
	 * @param int|null $user_id The ID of the user; the current user when null.
	 *
	 * @return bool Whether the user may edit the post's tickets.
	 */
	public function user_can_edit_tickets_of( int $post_id, ?int $user_id = null ): bool {
		$user_id     ??= get_current_user_id();
		$normalized_id = (int) Event::filter_event_id( $post_id, 'ticket_permissions' );
		// `get_post()` answers 0 with the global post, which is not the post asked about.
		$post = $normalized_id > 0 ? get_post( $normalized_id ) : null;

		// A filter may refuse edit rights on a post, never grant them on something that is not one.
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$can = user_can( $user_id, 'edit_post', $post->ID );

		/**
		 * Filters whether a user may edit the tickets of a post.
		 *
		 * @since TBD
		 *
		 * @param bool $can     Whether the user may edit the post's tickets.
		 * @param int  $post_id The ID of the post, with an occurrence ID normalized to its event.
		 * @param int  $user_id The ID of the user.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_user_can_edit_tickets_of', $can, $normalized_id, $user_id ) );
	}

	/**
	 * Whether a user may edit a ticket.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket  The ticket.
	 * @param int|null      $user_id The ID of the user; the current user when null.
	 *
	 * @return bool Whether the user may edit the ticket.
	 */
	public function user_can_edit_ticket( Ticket_Object $ticket, ?int $user_id = null ): bool {
		$user_id ??= get_current_user_id();

		/**
		 * Filters whether a user may edit a ticket. A refusal also refuses deleting it.
		 *
		 * @since TBD
		 *
		 * @param bool          $can     Whether the user may edit the ticket: whether they may edit its post.
		 * @param Ticket_Object $ticket  The ticket.
		 * @param int           $user_id The ID of the user.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_user_can_edit_ticket', $this->user_can_edit_tickets_of( (int) $ticket->get_event_id(), $user_id ), $ticket, $user_id ) );
	}

	/**
	 * Whether a user may delete a ticket.
	 *
	 * Deleting requires editing the ticket and `delete_post` on the ticket itself, the post being
	 * deleted. The legacy `tribe_tickets_current_user_can_delete_ticket` filter is not asked: it takes
	 * no user, so it cannot answer for a given one.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket  The ticket.
	 * @param int|null      $user_id The ID of the user; the current user when null.
	 *
	 * @return bool Whether the user may delete the ticket.
	 */
	public function user_can_delete_ticket( Ticket_Object $ticket, ?int $user_id = null ): bool {
		$user_id ??= get_current_user_id();

		if ( ! $this->user_can_edit_ticket( $ticket, $user_id ) ) {
			return false;
		}

		$can = user_can( $user_id, 'delete_post', (int) $ticket->ID );

		/**
		 * Filters whether a user may delete a ticket they may edit.
		 *
		 * @since TBD
		 *
		 * @param bool          $can     Whether the user may delete the ticket.
		 * @param Ticket_Object $ticket  The ticket.
		 * @param int           $user_id The ID of the user.
		 */
		return tribe_is_truthy( apply_filters( 'tec_tickets_user_can_delete_ticket', $can, $ticket, $user_id ) );
	}
}
