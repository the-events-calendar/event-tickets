<?php


/**
 * Class Tribe__Tickets__REST__V1__Service_Provider
 *
 * Add Event Tickets REST API
 *
 * @since TBD
 */
class Tribe__Tickets__REST__V1__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Binds and sets up implementations.
	 */
	public $namespace;

	/**
	 * Registers the classes and functionality needed fro REST API
	 *
	 * @since TBD
	 */
	public function register() {
		tribe_singleton( 'tickets.rest-v1.main', 'Tribe__Tickets__REST__V1__Main', array( 'hook' ) );
		tribe_singleton( 'tickets.rest-v1.messages', 'Tribe__Tickets__REST__V1__Messages' );
		tribe_singleton( 'tickets.rest-v1.headers-base', 'Tribe__Tickets__REST__V1__Headers__Base' );
		tribe_singleton( 'tickets.rest-v1.settings', 'Tribe__Tickets__REST__V1__Settings' );
		tribe_singleton( 'tickets.rest-v1.system', 'Tribe__Tickets__REST__V1__System' );
		tribe_singleton( 'tickets.rest-v1.validator', 'Tribe__Tickets__REST__V1__Validator__Base' );
		tribe_singleton( 'tickets.rest-v1.repository', 'Tribe__Tickets__REST__V1__Post_Repository' );
		tribe_singleton( 'tickets.rest-v1.flags', 'Tribe__Tickets__REST__V1__Flags' );
		tribe_singleton(
			'tickets.rest-v1.endpoints.documentation',
			new Tribe__Tickets__REST__V1__Endpoints__Swagger_Documentation(
				tribe( 'tickets.rest-v1.main' )->get_semantic_version()
			)
		);
		tribe_singleton(
			'tickets.rest-v1.endpoints.tickets-single',
			new Tribe__Tickets__REST__V1__Endpoints__Single_Ticket(
				tribe( 'tickets.rest-v1.messages' ),
				tribe( 'tickets.rest-v1.repository' ),
				tribe( 'tickets.rest-v1.validator' )
			)
		);
		tribe_singleton(
			'tickets.rest-v1.endpoints.tickets-archive',
			new Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive(
				tribe( 'tickets.rest-v1.messages' ),
				tribe( 'tickets.rest-v1.repository' ),
				tribe( 'tickets.rest-v1.validator' )
			)
		);

		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/functions/advanced-functions/rest-v1.php';

		$this->hooks();
	}

	/**
	 * Registers the REST API endpoints for Event Tickets.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {
		$this->namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();

		$doc_endpoint = $this->register_documentation_endpoint();
		$this->register_single_ticket_endpoint();
		$this->register_ticket_archive_endpoint();

		// @todo add the endpoints as documentation providers here
		$doc_endpoint->register_documentation_provider( '/doc', $doc_endpoint );
		// @todo update the ticket definition here
		$doc_endpoint->register_definition_provider( 'Ticket', new Tribe__Tickets__REST__V1__Documentation__Ticket_Definition_Provider() );
		// @todo add the attendee documentation provider here
	}

	/**
	 * Builds, registers and returns the Swagger.io documentation provider endpoint.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Documentation__Swagger__Builder_Interface
	 */
	protected function register_documentation_endpoint() {
		$endpoint = tribe( 'tickets.rest-v1.endpoints.documentation' );

		register_rest_route( $this->namespace, '/doc', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $endpoint, 'get' ),
		) );

		return $endpoint;
	}

	/**
	 * Registers the REST API endpoint that will handle single ticket requests.
	 * 
	 * @since TBD
	 * 
	 * @return Tribe__Tickets__REST__V1__Endpoints__Single_Ticket
	 */
	protected function register_single_ticket_endpoint() {
		/** @var Tribe__Tickets__REST__V1__Endpoints__Single_Ticket $endpoint */
		$endpoint = tribe( 'tickets.rest-v1.endpoints.tickets-single' );

		register_rest_route( $this->namespace, '/tickets/(?P<id>\\d+)', array(
			'methods'  => WP_REST_Server::READABLE,
			'args'     => $endpoint->READ_args(),
			'callback' => array( $endpoint, 'get' ),
		) );

		return $endpoint;
	}

	/**
	 * Registers the REST API endpoint that will handle ticket archive requests.
	 *
	 * @since TBD
	 *        
	 * @return Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive
	 */
	protected function register_ticket_archive_endpoint() {
		/** @var Tribe__Tickets__REST__V1__Endpoints__Ticket_Archive $endpoint */
		$endpoint = tribe( 'tickets.rest-v1.endpoints.tickets-archive' );

		register_rest_route( $this->namespace, '/tickets', array(
			'methods'  => WP_REST_Server::READABLE,
			'args'     => $endpoint->READ_args(),
			'callback' => array( $endpoint, 'get' ),
		) );

		return $endpoint;
	}

	/**
	 * Hooks all the methods and actions the class needs.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );

		foreach ( Tribe__Tickets__Main::instance()->post_types() as $post_type ) {
			add_filter( "rest_prepare_{$post_type}", tribe_callback( 'tickets.rest-v1.flags', 'flag_ticketed_post' ), 10, 2 );
		}

		add_filter( 'tribe_rest_event_data', tribe_callback( 'tickets.rest-v1.flags', 'flag_ticketed_event' ), 10, 2 );
	}
}
