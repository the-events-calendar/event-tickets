<?php

/**
 * Class Tribe__Tickets__REST__V1__Post_Repository
 *
 * The base Ticket object repository, a decorator of the base one.
 *
 * @since TBD
 * @method  by_args( array $args )
 * @method  where_args( array $args )
 * @method  page( $page )
 * @method  per_page( $per_page )
 * @method  found()
 * @method  all()
 * @method  offset( $offset, $increment = false )
 * @method  order( $order = 'ASC' )
 * @method  order_by( $order_by )
 * @method  fields( $fields )
 * @method  permission( $permission )
 * @method  in( $post_ids )
 * @method  not_in( $post_ids )
 * @method  parent( $post_id )
 * @method  parent_in( $post_ids )
 * @method  parent_not_in( $post_ids )
 * @method  search( $search )
 * @method  count()
 * @method  filter_name( $filter_name )
 * @method  first()
 * @method  last()
 * @method  nth( $n )
 * @method  take( $n )
 * @method  by_primary_key( $primary_key )
 */
class Tribe__Tickets__REST__V1__Ticket_Repository implements Tribe__Repository__Interface {

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
		$this->decorated_repository = tribe( 'tickets.ticket-repository' );
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

	/**
	 * {@inheritdoc}
	 */
	public function update( Tribe__Repository__Read_Interface $read = null ) {
		// @todo review this when allowing updates from REST API
		return $this->decorated_repository->update( $read );
	}

	/**
	 * {@inheritdoc}
	 */
	public function by( $key, $value ) {
		return call_user_func_array( array( $this->fetch(), 'by' ), func_get_args() );
	}

	/**
	 * Returns a REST API v1 specific Read repository.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Tickets__REST__V1__Repositories__Ticket_Read
	 */
	public function fetch() {
		return new Tribe__Tickets__REST__V1__Repositories__Ticket_Read(
			$this->decorated_repository->read_schema,
			tribe()->make( 'Tribe__Repository__Query_Filters' ),
			$this->decorated_repository->default_args,
			$this->decorated_repository
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function where( $key, $value ) {
		return call_user_func_array( array( $this->fetch(), 'where' ), func_get_args() );
	}

	/**
	 * Forwards calls to the decorated repository.
	 *
	 * @since TBD
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return mixed
	 */
	public function __call( $name, $args ) {
		// @todo review this when adding updates
		return call_user_func_array( array( $this->fetch(), $name ), $args );
	}
}
