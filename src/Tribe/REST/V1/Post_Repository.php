<?php


class Tribe__Tickets__REST__V1__Post_Repository implements Tribe__Tickets__REST__Interfaces__Post_Repository {

	/**
	 * @var Tribe__REST__Messages_Interface
	 */
	protected $messages;

	public function __construct( Tribe__REST__Messages_Interface $messages = null ) {
		$this->messages = $messages ? $messages : tribe( 'tickets.rest-v1.messages' );
	}

	/**
	 * Returns ticket id
	 *
	 * @todo add return ticket based on service provider
	 *
	 * @since TBD
	 *
	 * @param int    $ticket_id A ticket post ID.
	 * @param string $context  Context of data.
	 *
	 * @return $ticket_id.
	 *
	 */
	public function get_ticket_data( $ticket_id, $context = '' ) {
		return $ticket_id;
	}

}