<?php
/**
 * The checks a payload passes before anything acts on it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */

namespace TEC\Tickets\Deferred_Save;

use TEC\Tickets\Deferred_Save\Payload\Outcome;
use TEC\Tickets\Deferred_Save\Payload\Parser;
use TEC\Tickets\Deferred_Save\Payload\Rejections;
use TEC\Tickets\Event;
use TEC\Tickets\Ticket_Data;
use TEC\Tickets\Ticket_Permissions;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Checks.
 *
 * Checks a parsed payload against the post being saved: who may act, and on which tickets. A
 * failed check rejects its entry, or the whole payload when the user cannot edit the post.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Deferred_Save
 */
final class Checks {
	/**
	 * Loads tickets.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Answers who may edit or delete a ticket.
	 *
	 * @since TBD
	 *
	 * @var Ticket_Permissions
	 */
	private Ticket_Permissions $permissions;

	/**
	 * Checks constructor.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Data        $ticket_data Loads tickets.
	 * @param Ticket_Permissions $permissions Answers who may edit or delete a ticket.
	 */
	public function __construct( Ticket_Data $ticket_data, Ticket_Permissions $permissions ) {
		$this->ticket_data = $ticket_data;
		$this->permissions = $permissions;
	}

	/**
	 * Checks a payload against the post being saved.
	 *
	 * @since TBD
	 *
	 * @param Payload $payload The parsed payload.
	 * @param int     $post_id The ID of the post being saved. An occurrence ID is normalized to its event.
	 *
	 * @return Outcome The entries that passed, and the ones that failed with why.
	 */
	public function run( Payload $payload, int $post_id ): Outcome {
		$rejections = new Rejections();

		if ( ! $payload->has_changes() ) {
			return new Outcome( $payload, $rejections );
		}

		$post_id = (int) Event::filter_event_id( $post_id, 'deferred_save' );

		if ( ! $this->permissions->user_can_edit_tickets_of( $post_id ) ) {
			return new Outcome(
				new Payload(),
				$rejections->with( null, null, __( 'You are not allowed to edit the tickets of this post.', 'event-tickets' ) )
			);
		}

		$update = $payload->get_update();
		$move   = $payload->get_move();
		$delete = [];

		foreach ( array_keys( $update ) as $ticket_id ) {
			$ticket = $this->get_ticket_on_post( $ticket_id, $post_id );

			if ( null === $ticket ) {
				$rejections = $rejections->with( Parser::UPDATE, $ticket_id, $this->not_on_post_message( $ticket_id ), true );
				unset( $update[ $ticket_id ] );
				continue;
			}

			if ( ! $this->permissions->for_ticket( $ticket )->user_can_edit_ticket( $ticket ) ) {
				$rejections = $rejections->with( Parser::UPDATE, $ticket_id, $this->cannot_edit_message( $ticket_id ) );
				unset( $update[ $ticket_id ] );
			}
		}

		foreach ( $move as $ticket_id => $destination_id ) {
			$ticket = $this->get_ticket_on_post( $ticket_id, $post_id );

			if ( null === $ticket ) {
				$rejections = $rejections->with( Parser::MOVE, $ticket_id, $this->not_on_post_message( $ticket_id ), true );
				unset( $move[ $ticket_id ] );
				continue;
			}

			if ( ! $this->permissions->for_ticket( $ticket )->user_can_edit_ticket( $ticket ) ) {
				$rejections = $rejections->with( Parser::MOVE, $ticket_id, $this->cannot_edit_message( $ticket_id ) );
				unset( $move[ $ticket_id ] );
				continue;
			}

			// The destination is passed on in the form it is checked in.
			$destination_id     = (int) Event::filter_event_id( $destination_id, 'deferred_save' );
			$move[ $ticket_id ] = $destination_id;

			// Whether the destination can hold this ticket is the move's concern (SOFT-4825); that the user may edit its tickets is ours.
			if ( ! $this->permissions->user_can_edit_tickets_of( $destination_id ) ) {
				$rejections = $rejections->with(
					Parser::MOVE,
					$ticket_id,
					sprintf(
						/* translators: %d: the destination post ID. */
						__( 'You are not allowed to move tickets to post %d.', 'event-tickets' ),
						$destination_id
					)
				);
				unset( $move[ $ticket_id ] );
			}
		}

		foreach ( $payload->get_delete() as $ticket_id ) {
			$ticket = $this->get_ticket_on_post( $ticket_id, $post_id );

			if ( null === $ticket ) {
				$rejections = $rejections->with( Parser::DELETE, $ticket_id, $this->not_on_post_message( $ticket_id ), true );
				continue;
			}

			if ( ! $this->permissions->for_ticket( $ticket )->user_can_delete_ticket( $ticket ) ) {
				$rejections = $rejections->with(
					Parser::DELETE,
					$ticket_id,
					sprintf(
						/* translators: %d: the ticket ID. */
						__( 'You are not allowed to delete ticket %d.', 'event-tickets' ),
						$ticket_id
					)
				);
				continue;
			}

			$delete[] = $ticket_id;
		}

		return new Outcome( new Payload( $update, $payload->get_create(), $delete, $move ), $rejections );
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
		$ticket = $this->ticket_data->load_ticket_object( $ticket_id );

		if ( ! $ticket instanceof Ticket_Object ) {
			return null;
		}

		$ticket_post_id = (int) Event::filter_event_id( (int) $ticket->get_event_id(), 'deferred_save' );

		return $ticket_post_id === $post_id ? $ticket : null;
	}

	/**
	 * The message for an entry whose ticket the current user may not edit.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket ID from the payload.
	 *
	 * @return string The message.
	 */
	private function cannot_edit_message( int $ticket_id ): string {
		return sprintf(
			/* translators: %d: the ticket ID. */
			__( 'You are not allowed to edit ticket %d.', 'event-tickets' ),
			$ticket_id
		);
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
