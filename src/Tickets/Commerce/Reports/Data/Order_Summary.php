<?php
namespace TEC\Tickets\Commerce\Reports\Data;

use Tribe__Tickets__Tickets;

class Order_Summary {
	private int $post_id;

	/**
	 * @var \Tribe__Tickets__Ticket_Object[]
	 */
	private array $tickets;
	
	/**
	 * @var array|array[]
	 */
	private array $tickets_by_type;

	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->tickets_by_type = [ 'rsvp' => [], 'default' => [] ];
	}

	public function get_data( int $post_id ) {
		$post     = get_post( $post_id );
		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider_object( $post_id );
		$this->tickets  = $provider->get_tickets( $post_id );

		foreach ( $this->tickets as $ticket ) {
			// $this->tickets_by_type[ $ticket->type() ][] = $ticket;
			// ticket label
			// ticket type
			// ticket price
			// ticket quantity by status
		}
	}
}