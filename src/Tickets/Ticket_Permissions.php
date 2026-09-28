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
 * Answers whether the current user may edit or delete a ticket. By default the answer is the one
 * Event Tickets has always given: whoever may edit the ticket's post may edit and delete its
 * tickets. A provider that needs more, such as WooCommerce requiring rights on the product, ships
 * a subclass and returns it from the `tec_tickets_ticket_permissions` filter for its tickets.
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
	 * Whether the current user may edit the tickets of a post.
	 *
	 * This is the rule `Tribe__Tickets__Metabox::has_permission()` applies to every ticket write,
	 * without its nonce check: the `edit_event_tickets` capability, or the post type's
	 * `edit_others_posts`, or `edit_post` on the post.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The ID of the post. An occurrence ID is normalized to its event.
	 *
	 * @return bool Whether the current user may edit the post's tickets.
	 */
	public function current_user_can_edit_tickets_of( int $post_id ): bool {
		$post = get_post( (int) Event::filter_event_id( $post_id, 'ticket_permissions' ) );

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		$post_type = get_post_type_object( $post->post_type );

		// The capability is not registered by Event Tickets; it is kept for parity with today's rule, which lets a site grant it to a role.
		return current_user_can( 'edit_event_tickets' ) // phpcs:ignore WordPress.WP.Capabilities.Unknown
			|| ( $post_type && current_user_can( $post_type->cap->edit_others_posts ) )
			|| current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Whether the current user may edit a ticket.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket The ticket.
	 *
	 * @return bool Whether the current user may edit the ticket.
	 */
	public function current_user_can_edit_ticket( Ticket_Object $ticket ): bool {
		return $this->current_user_can_edit_tickets_of( (int) $ticket->get_event_id() );
	}

	/**
	 * Whether the current user may delete a ticket.
	 *
	 * Deleting requires editing, and then passes the per-ticket filter Event Tickets has exposed
	 * since 4.6, so today's integrations keep their say.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket The ticket.
	 *
	 * @return bool Whether the current user may delete the ticket.
	 */
	public function current_user_can_delete_ticket( Ticket_Object $ticket ): bool {
		if ( ! $this->current_user_can_edit_ticket( $ticket ) ) {
			return false;
		}

		/** This filter is documented in src/Tribe/Tickets.php */
		return (bool) apply_filters( 'tribe_tickets_current_user_can_delete_ticket', true, $ticket->ID, $ticket->provider_class );
	}
}
