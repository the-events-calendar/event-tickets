<?php

namespace Tribe\Tickets\Test\Factories\REST\V1;

use Tribe\Tickets\Test\Factories\RSVP_Attende;

class Ticket_Response extends RSVP_Attende {

	public function create( $args = array(), $generation_definitions = null ) {
		return $this->create_and_get( $args, $generation_definitions );
	}

	public function create_and_get( $args = array(), $generation_definitions = null ) {
		$repository = new \Tribe__Tickets__REST__V1__Post_Repository( new \Tribe__Tickets__REST__V1__Messages() );

		$data = $repository->get_attendee_data( parent::create( $args, $generation_definitions ) );

		return $data;
	}
}
