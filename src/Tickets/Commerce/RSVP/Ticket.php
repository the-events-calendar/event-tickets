<?php
/**
 * Handles modifications to Ticket Objects for RSVP in Tickets Commerce.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */

namespace TEC\Tickets\Commerce\RSVP;

/**
 * Class Ticket.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\RSVP
 */
class Ticket {

	/**
	 * Meta key that holds the "not going" option visibility status.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public $show_not_going = '_tribe_ticket_show_not_going';

	/**
	 * Filters RSVP ticket object to add "not going" option visibility.
	 *
	 * @since TBD
	 *
	 * @param object $return     The RSVP ticket object being filtered.
	 * @param int    $event_id   The ID of the event the ticket belongs to.
	 * @param int    $ticket_id  The ID of the RSVP ticket.
	 *
	 * @return object The modified RSVP ticket object.
	 */
	public function filter_rsvp( $return, $event_id, $ticket_id ) {
		if ( $return->type !== 'tc-rsvp' ) {
			return $return;
		}

		$return->show_not_going = get_post_meta( $ticket_id, $this->show_not_going, true );

		return $return;
	}

	/**
	 * Saves the "not going" option status for RSVP tickets.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id     The post ID of the RSVP ticket.
	 * @param object $ticket      The RSVP ticket object.
	 * @param array  $raw_data    Raw data from the form submission.
	 * @param string $ticket_class The class type of the ticket.
	 */
	public function save_rsvp( $post_id, $ticket, $raw_data, $ticket_class ) {
		if ( $ticket->type !== 'tc-rsvp' ) {
			return;
		}

		$show_not_going = 'no';

		if ( isset( $raw_data['tec_tickets_rsvp_enable_cannot_go'] ) ) {
			$show_not_going = $raw_data['tec_tickets_rsvp_enable_cannot_go'];
		}

		$show_not_going = tribe_is_truthy( $show_not_going ) ? 'yes' : 'no';
		update_post_meta( $ticket->ID, $this->show_not_going, $show_not_going );
	}
}
