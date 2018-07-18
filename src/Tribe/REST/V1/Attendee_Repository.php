<?php

/**
 * Class Tribe__Tickets__REST__V1__Attendee_Repository
 *
 * The base Attendee object repository, a decorator of the base one.
 *
 * @since TBD
 */
class Tribe__Tickets__REST__V1__Attendee_Repository
	implements Tribe__Repository__Interface {
	/**
	 * @var Tribe__Repository__Interface
	 */
	protected $decorated_repository;

	/**
	 * Tribe__Tickets__REST__V1__Attendee_Repository constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->decorated_repository = tribe( 'tickets.attendee-repository' );
	}

	/**
	 * Returns a REST API v1 specific Read repository.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__REST__V1__Repositories__Attendee_Read
	 */
	public function fetch() {
		return new Tribe__Tickets__REST__V1__Repositories__Attendee_Read(
			$this->decorated_repository->read_schema,
			tribe()->make( 'Tribe__Repository__Query_Filters' ),
			$this->decorated_repository->default_args
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default_args() {
		return $this->decorated_repository->get_default_args();
	}

	/**
	 * {@inheritdoc}
	 */
	public function set_default_args( array $default_args ) {
		return $this->decorated_repository->set_default_args( $default_args );
	}

	public function update() {
		// TODO: Implement update() method.
	}
}
