<?php
/**
 * Makes the tickets of a recurring event recurring event tickets.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Commerce\Ticket as Commerce_Ticket;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Events__Main as TEC;

/**
 * Sets the `recurring` type on a ticket saved on a recurring event, whatever saved it.
 *
 * Every save path writes `_type` from the request, falling back to `default`, and the block editor sends no type. So
 * the type is set after the save writes it last, on `tribe_tickets_ticket_add`, and a ticket that was a template
 * before the save stays one.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Ticket_Type {
	/**
	 * The IDs of the tickets being saved that were templates before the save.
	 *
	 * @since TBD
	 *
	 * @var array<int,true>
	 */
	private array $templates = [];

	/**
	 * The template guard.
	 *
	 * @since TBD
	 *
	 * @var Template_Guard
	 */
	private Template_Guard $guard;

	/**
	 * Ticket_Type constructor.
	 *
	 * @since TBD
	 *
	 * @param Template_Guard $guard The template guard.
	 */
	public function __construct( Template_Guard $guard ) {
		$this->guard = $guard;
	}

	/**
	 * Remembers whether a ticket about to be saved is a template, before the save overwrites its type.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id The ticket's post.
	 * @param Ticket_Object $ticket  The ticket being saved.
	 *
	 * @return void
	 */
	public function remember( $post_id, $ticket ): void {
		$ticket_id = $ticket instanceof Ticket_Object ? (int) $ticket->ID : 0;

		if ( $ticket_id && $this->guard->is_template( $ticket_id ) ) {
			$this->templates[ $ticket_id ] = true;
		}
	}

	/**
	 * Sets the `recurring` type on a Tickets Commerce ticket just saved on a recurring event, or saved as a template.
	 *
	 * A ticket saved with another type than `default` keeps it.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id The ticket's post.
	 * @param Ticket_Object $ticket  The ticket just saved.
	 *
	 * @return void
	 */
	public function assign( $post_id, $ticket ): void {
		$ticket_id = $ticket instanceof Ticket_Object ? (int) $ticket->ID : 0;

		if ( ! $ticket_id || Commerce_Ticket::POSTTYPE !== get_post_type( $ticket_id ) ) {
			return;
		}

		$was_template = isset( $this->templates[ $ticket_id ] );
		unset( $this->templates[ $ticket_id ] );

		if ( ! in_array( get_post_meta( $ticket_id, '_type', true ), [ '', 'default' ], true ) ) {
			return;
		}

		if ( $was_template || $this->is_recurring_event( (int) $post_id ) ) {
			update_post_meta( $ticket_id, '_type', Template_Guard::TICKET_TYPE );
		}
	}

	/**
	 * Whether a post is a recurring event.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The post.
	 *
	 * @return bool Whether the post is a recurring event.
	 */
	private function is_recurring_event( int $post_id ): bool {
		return TEC::POSTTYPE === get_post_type( $post_id )
			&& function_exists( 'tribe_is_recurring_event' )
			&& tribe_is_recurring_event( $post_id );
	}
}
