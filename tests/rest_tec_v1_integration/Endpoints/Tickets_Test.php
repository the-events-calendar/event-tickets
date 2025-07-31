<?php

namespace TEC\Tickets\Tests\REST\TEC\V1\Endpoints;

use TEC\Tickets\REST\TEC\V1\Endpoints\Tickets;

class Tickets_Test extends Ticket_Test {
	protected $endpoint_class = Tickets::class;

	public function test_get_formatted_entity() {
		[ $ticketable_posts, $tickets ] = $this->create_test_data();

		$data = [];
		foreach ( $tickets as $ticket ) {
			// Get the ticket post object directly
			$ticket_post = tec_tc_get_ticket( $ticket );
			$data[] = $this->endpoint->get_formatted_entity( $ticket_post );
		}

		$json = wp_json_encode( $data, JSON_SNAPSHOT_OPTIONS );

		$json = str_replace( $ticketable_posts, '{POST_ID}', $json );
		$json = str_replace( $tickets, '{TICKET_ID}', $json );

		$this->assertMatchesJsonSnapshot( $json );
	}
}
