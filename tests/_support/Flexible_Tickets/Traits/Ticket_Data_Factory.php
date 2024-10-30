<?php

namespace TEC\Tickets\Flexible_Tickets\Test\Traits;

use Tribe__Tickets__Ticket_Object as Ticket;

trait Ticket_Data_Factory {
	private function data_for_ticket( Ticket $ticket, array $capacity_payload = [ 'mode' => '' ] ): array {
		return [
			'ticket_name'        => "Test TC ticket for $ticket->ID",
			'ticket_description' => "Test TC ticket description for $ticket->ID",
			'ticket_price'       => get_post_meta( $ticket->ID, '_price', true ),
			'tribe-ticket'       => $capacity_payload,
		];
	}
}