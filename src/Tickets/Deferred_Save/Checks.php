<?php
/**
 * The checks a payload passes before anything acts on it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Tickets\Event;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Checks.
 *
 * Runs the payload against the post being saved. The current user must be able to edit that
 * post, or the whole payload is rejected. Every `update`, `delete` and `move` entry must name a
 * ticket attached to that post, and every `delete` entry must pass the per-ticket delete
 * permission filter Event Tickets already exposes; a failed check rejects that entry only.
 *
 * `create` entries carry no ticket ID and pass through. Their `data` is not interpreted here:
 * the providers sanitize it when the ticket is saved, as they do today.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
class Checks {
	/**
	 * Checks a payload against the post being saved.
	 *
	 * @since TBD
	 *
	 * @param Payload $payload The parsed payload.
	 * @param int     $post_id The ID of the post being saved. An occurrence ID is normalized to its event.
	 *
	 * @return Payload A payload holding the entries that passed, with an error for each one that did not.
	 */
	public function run( Payload $payload, int $post_id ): Payload {
		if ( ! $payload->is_valid() || ! $payload->has_changes() ) {
			return $payload;
		}

		$post_id = (int) Event::filter_event_id( $post_id, 'deferred_save' );

		if ( ! $this->current_user_can_edit( $post_id ) ) {
			return $payload->with_rejected(
				null,
				null,
				__( 'You are not allowed to edit the tickets of this post.', 'event-tickets' )
			);
		}

		foreach ( array_keys( $payload->get_update() ) as $ticket_id ) {
			if ( ! $this->ticket_belongs_to_post( $ticket_id, $post_id ) ) {
				$payload = $payload->with_rejected( Payload::UPDATE, $ticket_id, $this->not_on_post_message( $ticket_id ) );
			}
		}

		foreach ( array_keys( $payload->get_move() ) as $ticket_id ) {
			if ( ! $this->ticket_belongs_to_post( $ticket_id, $post_id ) ) {
				$payload = $payload->with_rejected( Payload::MOVE, $ticket_id, $this->not_on_post_message( $ticket_id ) );
			}
		}

		foreach ( $payload->get_delete() as $ticket_id ) {
			$ticket = $this->get_ticket_on_post( $ticket_id, $post_id );

			if ( null === $ticket ) {
				$payload = $payload->with_rejected( Payload::DELETE, $ticket_id, $this->not_on_post_message( $ticket_id ) );
				continue;
			}

			if ( ! $this->current_user_can_delete( $ticket ) ) {
				$payload = $payload->with_rejected(
					Payload::DELETE,
					$ticket_id,
					sprintf(
						/* translators: %d: the ticket ID. */
						__( 'You are not allowed to delete ticket %d.', 'event-tickets' ),
						$ticket_id
					)
				);
			}
		}

		return $payload;
	}

	/**
	 * Whether the current user may edit the tickets of a post.
	 *
	 * This is the capability rule `Tribe__Tickets__Metabox::has_permission()` applies to every
	 * ticket write today, without its nonce check: the post save carries its own nonce.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The normalized post ID.
	 *
	 * @return bool Whether the current user may edit the post's tickets.
	 */
	private function current_user_can_edit( int $post_id ): bool {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		$post_type = get_post_type_object( $post->post_type );

		// The capability is not registered by Event Tickets; it is kept for parity with today's rule, which lets a site grant it to a role.
		return current_user_can( 'edit_event_tickets' ) // phpcs:ignore WordPress.WP.Capabilities.Unknown
			|| ( $post_type && current_user_can( $post_type->cap->edit_others_posts ) )
			|| current_user_can( 'edit_post', $post->ID );
	}

	/**
	 * Whether a ticket ID names a ticket attached to the post.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID from the payload.
	 * @param int $post_id   The normalized post ID.
	 *
	 * @return bool Whether the ticket belongs to the post.
	 */
	private function ticket_belongs_to_post( int $ticket_id, int $post_id ): bool {
		return null !== $this->get_ticket_on_post( $ticket_id, $post_id );
	}

	/**
	 * Loads a ticket, provided the ID is a ticket and it is attached to the post.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID from the payload.
	 * @param int $post_id   The normalized post ID.
	 *
	 * @return Ticket_Object|null The ticket, or `null` when the ID is not a ticket on this post.
	 */
	private function get_ticket_on_post( int $ticket_id, int $post_id ): ?Ticket_Object {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider instanceof Tickets ) {
			return null;
		}

		// Providers return `null` for an ID that exists but is not one of their tickets, e.g. an attendee.
		$ticket = $provider->get_ticket( $post_id, $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			return null;
		}

		$ticket_post_id = (int) Event::filter_event_id( (int) $ticket->get_event_id(), 'deferred_save' );

		return $ticket_post_id === $post_id ? $ticket : null;
	}

	/**
	 * Whether the current user may delete a ticket, according to the filter Event Tickets already exposes.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket The ticket.
	 *
	 * @return bool Whether the current user may delete the ticket.
	 */
	private function current_user_can_delete( Ticket_Object $ticket ): bool {
		/** This filter is documented in src/Tribe/Tickets.php */
		return (bool) apply_filters( 'tribe_tickets_current_user_can_delete_ticket', true, $ticket->ID, $ticket->provider_class );
	}

	/**
	 * The message for an entry whose ticket is not on the post being saved.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID from the payload.
	 *
	 * @return string The message.
	 */
	private function not_on_post_message( int $ticket_id ): string {
		return sprintf(
			/* translators: %d: the ticket ID. */
			__( 'Ticket %d does not belong to this post.', 'event-tickets' ),
			$ticket_id
		);
	}
}
