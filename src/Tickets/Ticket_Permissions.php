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
 * Answers whether a user may edit or delete a ticket. By default whoever may edit the ticket's
 * post may edit its tickets, and delete those they may also delete as posts; each answer passes
 * through its own filter, last. A provider that needs more, such as WooCommerce requiring rights on
 * the product, ships a subclass overriding `can_edit_ticket()` and returns it from the
 * `tec_tickets_ticket_permissions` filter for its tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets
 */
class Ticket_Permissions {
	/**
	 * Returns the permissions that answer for a ticket.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket The ticket.
	 *
	 * @return Ticket_Permissions This instance, unless a filter provides a subclass for the ticket.
	 */
	public function for_ticket( Ticket_Object $ticket ): Ticket_Permissions {
		/**
		 * Filters which permissions answer for a ticket.
		 *
		 * A provider whose tickets need their own rule returns an instance of a `Ticket_Permissions`
		 * subclass for them. Anything that is not a `Ticket_Permissions` is ignored.
		 *
		 * @since TBD
		 *
		 * @param Ticket_Permissions $permissions The default permissions.
		 * @param Ticket_Object      $ticket      The ticket being asked about.
		 */
		$permissions = apply_filters( 'tec_tickets_ticket_permissions', $this, $ticket );

		return $permissions instanceof self ? $permissions : $this;
	}

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
	final public function user_can_edit_tickets_of( int $post_id, ?int $user_id = null ): bool {
		$user_id     ??= get_current_user_id();
		$normalized_id = (int) Event::filter_event_id( $post_id, 'ticket_permissions' );
		// `get_post()` answers 0 with the global post, which is not the post asked about.
		$post = $normalized_id > 0 ? get_post( $normalized_id ) : null;
		$can  = $post instanceof WP_Post && user_can( $user_id, 'edit_post', $post->ID );

		/**
		 * Filters whether a user may edit the tickets of a post.
		 *
		 * @since TBD
		 *
		 * @param bool $can     Whether the user may edit the post's tickets.
		 * @param int  $post_id The ID of the post, as asked about.
		 * @param int  $user_id The ID of the user.
		 */
		return (bool) apply_filters( 'tec_tickets_user_can_edit_tickets_of', $can, $post_id, $user_id );
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
	final public function user_can_edit_ticket( Ticket_Object $ticket, ?int $user_id = null ): bool {
		$user_id ??= get_current_user_id();

		/**
		 * Filters whether a user may edit a ticket. A refusal also refuses deleting it.
		 *
		 * @since TBD
		 *
		 * @param bool          $can     Whether the user may edit the ticket, as the permissions for it answered.
		 * @param Ticket_Object $ticket  The ticket.
		 * @param int           $user_id The ID of the user.
		 */
		return (bool) apply_filters( 'tec_tickets_user_can_edit_ticket', $this->can_edit_ticket( $ticket, $user_id ), $ticket, $user_id );
	}

	/**
	 * The rule for editing a ticket, before the filter: whether the user may edit the ticket's post.
	 *
	 * A provider whose tickets need more overrides this, so the filter still has the last word.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket  The ticket.
	 * @param int           $user_id The ID of the user.
	 *
	 * @return bool Whether the user may edit the ticket.
	 */
	protected function can_edit_ticket( Ticket_Object $ticket, int $user_id ): bool {
		return $this->user_can_edit_tickets_of( (int) $ticket->get_event_id(), $user_id );
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
	final public function user_can_delete_ticket( Ticket_Object $ticket, ?int $user_id = null ): bool {
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
		return (bool) apply_filters( 'tec_tickets_user_can_delete_ticket', $can, $ticket, $user_id );
	}
}
