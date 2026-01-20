<?php
/**
 * RSVP V2 Meta Fields handler.
 *
 * Handles saving RSVP-specific meta fields when tickets are saved via Tickets Commerce.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Meta_Fields
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Meta_Fields {
	/**
	 * Save the "show not going" option for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int           $post_id  The event/post ID.
	 * @param Ticket_Object $ticket   The ticket object.
	 * @param array         $raw_data The raw ticket data.
	 */
	public function save_show_not_going( int $post_id, Ticket_Object $ticket, array $raw_data ): void {
		if ( ! $this->is_rsvp_ticket( $ticket, $raw_data ) ) {
			return;
		}

		if ( ! isset( $raw_data['ticket_rsvp_enable_cannot_go'] ) ) {
			return;
		}

		$show_not_going = tribe_is_truthy( $raw_data['ticket_rsvp_enable_cannot_go'] ) ? '1' : '';
		update_post_meta( $ticket->ID, Constants::SHOW_NOT_GOING_META_KEY, $show_not_going );
	}

	/**
	 * Check if the ticket is an RSVP ticket.
	 *
	 * @since TBD
	 *
	 * @param Ticket_Object $ticket   The ticket object.
	 * @param array         $raw_data The raw ticket data.
	 *
	 * @return bool Whether the ticket is an RSVP ticket.
	 */
	private function is_rsvp_ticket( Ticket_Object $ticket, array $raw_data ): bool {
		$ticket_type = $raw_data['ticket_type'] ?? get_post_meta( $ticket->ID, '_type', true );

		return Constants::TC_RSVP_TYPE === $ticket_type;
	}
}
