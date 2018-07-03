<?php

/**
 * Class Tribe__Tickets__REST__V1__Post_Repository
 *
 * @since TBD
 */
class Tribe__Tickets__REST__V1__Post_Repository_Extension extends Tribe__Tickets__Repository {

	/**
	 * Returns a REST API v1 specific Read repository.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__REST__V1__Read_Repository
	 */
	public function fetch() {
		return new Tribe__Tickets__REST__V1__Read_Repository(
			$this->read_schema,
			tribe()->make( 'Tribe__Repository__Query_Filters' ),
			$this->default_args
		);
	}
}
