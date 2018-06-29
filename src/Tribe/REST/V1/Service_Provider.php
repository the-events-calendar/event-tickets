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
		tribe_singleton( 'tickets.rest-v1.endpoints.documentation', new Tribe__Tickets__REST__V1__Endpoints__Swagger_Documentation( tribe( 'tickets.rest-v1.main' )->get_semantic_version() ) );

		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/functions/advanced-functions/rest-v1.php';

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );
	}

	/**
	 * Registers the REST API endpoints for Event Tickets.
	 *
	 * @since TBD
	 */
	public function register_endpoints() {

		$doc_endpoint = tribe( 'tickets.rest-v1.endpoints.documentation' );

		$this->namespace = tribe( 'tickets.rest-v1.main' )->get_events_route_namespace();

		register_rest_route( $this->namespace, '/doc', array(
			'methods'  => WP_REST_Server::READABLE,
			'callback' => array( $doc_endpoint, 'get' ),
		) );

		/** @var Tribe__Documentation__Swagger__Builder_Interface $documentation */
		$doc_endpoint->register_documentation_provider( '/doc', $doc_endpoint );
		$doc_endpoint->register_definition_provider( 'Ticket', new Tribe__Tickets__REST__V1__Documentation__Ticket_Definition_Provider() );
	}
}
