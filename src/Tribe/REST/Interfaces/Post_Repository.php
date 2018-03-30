<?php


interface Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * Returns an array representation of a ticket.
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id A ticket post ID.
	 * @param string $context  Context of data.
	 *
	 */
	public function get_ticket_data( $ticket_id, $context = '' );
}