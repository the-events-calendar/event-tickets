<?php

class Tribe__Tickets__REST__V1__Attendee_Repository
	extends Tribe__Tickets__Attendee_Repository {

	/**
	 * Returns a REST API v1 specific Read repository.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__REST__V1__Repositories__Ticket_Read
	 */
	public function fetch() {
		return new Tribe__Tickets__REST__V1__Repositories__Ticket_Read(
			$this->read_schema,
			tribe()->make( 'Tribe__Repository__Query_Filters' ),
			$this->default_args
		);
	}
}