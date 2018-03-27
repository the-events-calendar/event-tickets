<?php


/**
 * Class Tribe__Tickets__REST__V1__Main
 *
 * The main entry point for TEC REST API.
 *
 * This class should not contain business logic and merely set up and start the TEC REST API support.
 */
class Tribe__Tickets__REST__V1__Main extends Tribe__REST__Main {

	/**
	 * The Events Calendar REST API URL prefix.
	 *
	 * This prefx is appended to the Modern Tribe REST API URL ones.
	 *
	 * @var string
	 */
	protected $url_prefix = '/tickets/v1';

	/**
	 * @var array
	 */
	protected $registered_endpoints = array();

	/**
	 * Binds the implementations needed to support the REST API.
	 */
	public function bind_implementations() {
		tribe_singleton( 'tickets.rest-v1.messages', 'Tribe__Tickets__REST__V1__Messages' );
		tribe_singleton( 'tickets.rest-v1.headers-base', 'Tribe__Tickets__REST__V1__Headers__Base' );
		tribe_singleton( 'tickets.rest-v1.settings', 'Tribe__Tickets__REST__V1__Settings' );
		tribe_singleton( 'tickets.rest-v1.system', 'Tribe__Tickets__REST__V1__System' );
		tribe_singleton( 'tickets.rest-v1.validator', 'Tribe__Tickets__REST__V1__Validator__Base' );
		tribe_singleton( 'tickets.rest-v1.repository', 'Tribe__Tickets__REST__V1__Post_Repository' );
		//tribe_singleton( 'tec.rest-v1.endpoints.single-venue', array( $this, 'build_single_venue_endpoint' ) );

		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/functions/advanced-functions/rest-v1.php';
	}

	/**
	 * Hooks the filters and actions required for the REST API support to kick in.
	 */
	public function hook() {
		$this->hook_headers();
		$this->hook_settings();

		/** @var Tribe__Tickets__REST__V1__System $system */
		$system = tribe( 'tickets.rest-v1.system' );

		if ( ! $system->supports_tec_rest_api() || ! $system->tec_rest_api_is_enabled() ) {
			return;
		}

		add_action( 'rest_api_init', array( $this, 'register_endpoints' ) );

	}

	/**
	 * Hooks the additional headers and meta tags related to the REST API.
	 */
	protected function hook_headers() {
		/** @var Tribe__Tickets__REST__V1__System $system */
		$system = tribe( 'tickets.rest-v1.system' );
		/** @var Tribe__REST__Headers__Base_Interface $headers_base */
		$headers_base = tribe( 'tickets.rest-v1.headers-base' );

		if ( ! $system->tec_rest_api_is_enabled() ) {
			if ( ! $system->supports_tec_rest_api() ) {
				tribe_singleton( 'tickets.rest-v1.headers', new Tribe__REST__Headers__Unsupported( $headers_base, $this ) );
			} else {
				tribe_singleton( 'tickets.rest-v1.headers', new Tribe__REST__Headers__Disabled( $headers_base ) );
			}
		} else {
			tribe_singleton( 'tickets.rest-v1.headers', new Tribe__REST__Headers__Supported( $headers_base, $this ) );
		}

		/** @var Tribe__REST__Headers__Headers_Interface $headers */
		$headers = tribe( 'tickets.rest-v1.headers' );

		add_action( 'wp_head', array( $headers, 'add_header' ), 10, 0 );
		add_action( 'template_redirect', array( $headers, 'send_header' ), 11, 0 );
	}

	/**
	 * Hooks the additional Events Settings related to the REST API.
	 */
	protected function hook_settings() {
		add_filter( 'tribe_addons_tab_fields', array(
			tribe( 'tickets.rest-v1.settings' ),
			'filter_tribe_addons_tab_fields'
		) );
	}

	/**
	 * Registers the endpoints, and the handlers, supported by the REST API
	 *
	 * @param bool $register_routes Whether routes should be registered as well or not.
	 */
	public function register_endpoints( $register_routes = true ) {
		$this->register_documentation_endpoint( $register_routes );
		$this->register_event_archives_endpoint( $register_routes );
		$this->register_single_event_endpoint( $register_routes );
		$this->register_single_event_slug_endpoint( $register_routes );
		$this->register_venue_archives_endpoint( $register_routes );
		$this->register_single_venue_endpoint( $register_routes );
		$this->register_single_venue_slug_endpoint( $register_routes );
		$this->register_organizer_archives_endpoint( $register_routes );
		$this->register_single_organizer_endpoint( $register_routes );
		$this->register_single_organizer_slug_endpoint( $register_routes );

		global $wp_version;

		if ( version_compare( $wp_version, '4.7', '>=' ) ) {
			$this->register_categories_endpoint( $register_routes );
			$this->register_tags_endpoint( $register_routes );
		}
	}

	/**
	 * Builds and hooks the documentation endpoint
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.5
	 */
	protected function register_documentation_endpoint( $register_routes = true ) {
		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Swagger_Documentation( $this->get_semantic_version() );

		tribe_singleton( 'tickets.rest-v1.endpoints.documentation', $endpoint );

		if ( $register_routes ) {
			register_rest_route( $this->get_events_route_namespace(), '/doc', array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $endpoint, 'get' ),
			) );
		}

		/** @var Tribe__Documentation__Swagger__Builder_Interface $documentation */
		$documentation = tribe( 'tickets.rest-v1.endpoints.documentation' );
		$documentation->register_documentation_provider( '/doc', $endpoint );
		$documentation->register_definition_provider( 'Event', new Tribe__Tickets__REST__V1__Documentation__Event_Definition_Provider() );
		$documentation->register_definition_provider( 'Venue', new Tribe__Tickets__REST__V1__Documentation__Venue_Definition_Provider() );
		$documentation->register_definition_provider( 'Organizer', new Tribe__Tickets__REST__V1__Documentation__Organizer_Definition_Provider() );
		$documentation->register_definition_provider( 'Image', new Tribe__Documentation__Swagger__Image_Definition_Provider() );
		$documentation->register_definition_provider( 'ImageSize', new Tribe__Documentation__Swagger__Image_Size_Definition_Provider() );
		$documentation->register_definition_provider( 'DateDetails', new Tribe__Documentation__Swagger__Date_Details_Definition_Provider() );
		$documentation->register_definition_provider( 'CostDetails', new Tribe__Documentation__Swagger__Cost_Details_Definition_Provider() );
		$documentation->register_definition_provider( 'Term', new Tribe__Documentation__Swagger__Term_Definition_Provider() );
	}

	protected function get_semantic_version() {
		return '1.0.0';
	}

	/**
	 * Returns the events REST API namespace string that should be used to register a route.
	 *
	 * @return string
	 */
	protected function get_events_route_namespace() {
		return $this->get_namespace() . '/events/' . $this->get_version();
	}

	/**
	 * Returns the string indicating the REST API version.
	 *
	 * @return string
	 */
	public function get_version() {
		return 'v1';
	}

	/**
	 * Builds and hooks the event archives endpoint
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.5
	 */
	protected function register_event_archives_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );
		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Archive_Event( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.archive-event', $endpoint );

		if ( $register_routes ) {
			register_rest_route( $this->get_events_route_namespace(), '/events', array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $endpoint, 'get' ),
				'args'     => $endpoint->READ_args(),
			) );
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/events', $endpoint );
	}

	/**
	 * Registers the endpoint that will handle requests for a single event.
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.5
	 */
	protected function register_single_event_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );
		$venue_endpoint = tribe( 'tickets.rest-v1.endpoints.single-venue' );
		$organizer_endpoint = tribe( 'tickets.rest-v1.endpoints.single-organizer' );

		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Event( $messages, $post_repository, $validator, $venue_endpoint, $organizer_endpoint );

		tribe_singleton( 'tickets.rest-v1.endpoints.single-event', $endpoint );

		$namespace = $this->get_events_route_namespace();

		if ( $register_routes ) {
			register_rest_route(
				$namespace,
				'/events/(?P<id>\\d+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'args'     => $endpoint->READ_args(),
						'callback' => array( $endpoint, 'get' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $endpoint->DELETE_args(),
						'permission_callback' => array( $endpoint, 'can_delete' ),
						'callback'            => array( $endpoint, 'delete' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $endpoint->EDIT_args(),
						'permission_callback' => array( $endpoint, 'can_edit' ),
						'callback'            => array( $endpoint, 'update' ),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/events', array(
					'methods'             => WP_REST_Server::CREATABLE,
					'args'                => $endpoint->CREATE_args(),
					'permission_callback' => array( $endpoint, 'can_create' ),
					'callback'            => array( $endpoint, 'create' ),
				)
			);
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/events/{id}', $endpoint );
	}

	/**
	 * Registers the endpoint that will handle requests for a single event slug.
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.5
	 */
	protected function register_single_event_slug_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );
		$venue_endpoint = tribe( 'tickets.rest-v1.endpoints.single-venue' );
		$organizer_endpoint = tribe( 'tickets.rest-v1.endpoints.single-organizer' );

		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Event_Slug( $messages, $post_repository, $validator, $venue_endpoint, $organizer_endpoint );

		tribe_singleton( 'tickets.rest-v1.endpoints.single-event-slug', $endpoint );

		$namespace = $this->get_events_route_namespace();

		if ( $register_routes ) {
			register_rest_route(
				$namespace,
				'/events/by-slug/(?P<slug>[^/]+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'args'     => $endpoint->READ_args(),
						'callback' => array( $endpoint, 'get' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $endpoint->DELETE_args(),
						'permission_callback' => array( $endpoint, 'can_delete' ),
						'callback'            => array( $endpoint, 'delete' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $endpoint->EDIT_args(),
						'permission_callback' => array( $endpoint, 'can_edit' ),
						'callback'            => array( $endpoint, 'update' ),
					),
				)
			);
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/events/by-slug/{slug}', $endpoint );
	}

	/**
	 * Returns the URL where the API users will find the API documentation.
	 *
	 * @return string
	 */
	public function get_reference_url() {
		return esc_attr( 'https://theeventscalendar.com/' );
	}

	/**
	 * Builds an instance of the single venue endpoint.
	 *
	 * @return Tribe__Tickets__REST__V1__Endpoints__Single_Venue
	 */
	public function build_single_venue_endpoint() {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );

		return new Tribe__Tickets__REST__V1__Endpoints__Single_Venue( $messages, $post_repository, $validator );
	}

	/**
	 * Builds an instance of the single organizer endpoint.
	 *
	 * @return Tribe__Tickets__REST__V1__Endpoints__Single_Organizer
	 */
	public function build_single_organizer_endpoint() {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );

		return new Tribe__Tickets__REST__V1__Endpoints__Single_Organizer( $messages, $post_repository, $validator );
	}

	/**
	 * Returns the REST API URL prefix that will be appended to the namespace.
	 *
	 * The prefix should be in the `/some/path` format.
	 *
	 * @return string
	 */
	protected function url_prefix() {
		return $this->url_prefix;
	}

	/**
	 * Registers the endpoint that will handle requests for a single venue.
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.6
	 */
	protected function register_single_venue_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );

		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Venue( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.single-venue', $endpoint );

		$namespace = $this->get_events_route_namespace();

		if ( $register_routes ) {
			register_rest_route(
				$namespace,
				'/venues/(?P<id>\\d+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'args'     => $endpoint->READ_args(),
						'callback' => array( $endpoint, 'get' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $endpoint->DELETE_args(),
						'permission_callback' => array( $endpoint, 'can_delete' ),
						'callback'            => array( $endpoint, 'delete' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $endpoint->EDIT_args(),
						'permission_callback' => array( $endpoint, 'can_edit' ),
						'callback'            => array( $endpoint, 'update' ),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/venues',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'args'                => $endpoint->CREATE_args(),
					'permission_callback' => array( $endpoint, 'can_create' ),
					'callback'            => array( $endpoint, 'create' ),
				)
			);
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/venues/{id}', $endpoint );
	}

	/**
	 * Registers the endpoint that will handle requests for a single venue slug.
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.6
	 */
	protected function register_single_venue_slug_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );

		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Venue_Slug( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.single-venue-slug', $endpoint );

		$namespace = $this->get_events_route_namespace();

		if ( $register_routes ) {
			register_rest_route(
				$namespace,
				'/venues/by-slug/(?P<slug>[^/]+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'args'     => $endpoint->READ_args(),
						'callback' => array( $endpoint, 'get' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $endpoint->DELETE_args(),
						'permission_callback' => array( $endpoint, 'can_delete' ),
						'callback'            => array( $endpoint, 'delete' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $endpoint->EDIT_args(),
						'permission_callback' => array( $endpoint, 'can_edit' ),
						'callback'            => array( $endpoint, 'update' ),
					),
				)
			);
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/venues/by-slug/{slug}', $endpoint );
	}

	/**
	 * Registers the endpoint that will handle requests for a single organizer.
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since bucket/full-rest-api
	 */
	protected function register_single_organizer_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );

		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Organizer( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.single-organizer', $endpoint );

		$namespace = $this->get_events_route_namespace();

		if ( $register_routes ) {
			register_rest_route(
				$namespace,
				'/organizers/(?P<id>\\d+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'args'     => $endpoint->READ_args(),
						'callback' => array( $endpoint, 'get' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $endpoint->DELETE_args(),
						'permission_callback' => array( $endpoint, 'can_delete' ),
						'callback'            => array( $endpoint, 'delete' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $endpoint->EDIT_args(),
						'permission_callback' => array( $endpoint, 'can_edit' ),
						'callback'            => array( $endpoint, 'update' ),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/organizers',
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'args'                => $endpoint->CREATE_args(),
					'permission_callback' => array( $endpoint, 'can_create' ),
					'callback'            => array( $endpoint, 'create' ),
				)
			);
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/organizers/{id}', $endpoint );
	}

	/**
	 * Registers the endpoint that will handle requests for a single organizer slug.
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since bucket/full-rest-api
	 */
	protected function register_single_organizer_slug_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );

		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Organizer_Slug( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.single-organizer-slug', $endpoint );

		$namespace = $this->get_events_route_namespace();

		if ( $register_routes ) {
			register_rest_route(
				$namespace,
				'/organizers/by-slug/(?P<slug>[^/]+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'args'     => $endpoint->READ_args(),
						'callback' => array( $endpoint, 'get' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $endpoint->DELETE_args(),
						'permission_callback' => array( $endpoint, 'can_delete' ),
						'callback'            => array( $endpoint, 'delete' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $endpoint->EDIT_args(),
						'permission_callback' => array( $endpoint, 'can_edit' ),
						'callback'            => array( $endpoint, 'update' ),
					),
				)
			);
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/organizers/by-slug/{slug}', $endpoint );
	}

	/**
	 * Builds and hooks the venue archives endpoint
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.6
	 */
	protected function register_venue_archives_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );
		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Archive_Venue( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.archive-venue', $endpoint );

		if ( $register_routes ) {
			register_rest_route( $this->get_events_route_namespace(), '/venues', array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $endpoint, 'get' ),
				'args'     => $endpoint->READ_args(),
			) );
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/venues', $endpoint );
	}

	/**
	 * Builds and hooks the organizer archives endpoint
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 *
	 * @since 4.6
	 */
	protected function register_organizer_archives_endpoint( $register_routes = true ) {
		$messages = tribe( 'tickets.rest-v1.messages' );
		$post_repository = tribe( 'tickets.rest-v1.repository' );
		$validator = tribe( 'tickets.rest-v1.validator' );
		$endpoint = new Tribe__Tickets__REST__V1__Endpoints__Archive_Organizer( $messages, $post_repository, $validator );

		tribe_singleton( 'tickets.rest-v1.endpoints.archive-organizer', $endpoint );

		if ( $register_routes ) {
			register_rest_route( $this->get_events_route_namespace(), '/organizers', array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => array( $endpoint, 'get' ),
				'args'     => $endpoint->READ_args(),
			) );
		}

		tribe( 'tickets.rest-v1.endpoints.documentation' )->register_documentation_provider( '/organizers', $endpoint );
	}

	/**
	 * Builds and hooks the event categories archives endpoint
	 *
	 * @since 4.6
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 */
	protected function register_categories_endpoint( $register_routes ) {
		$messages         = tribe( 'tickets.rest-v1.messages' );
		$post_repository  = tribe( 'tickets.rest-v1.repository' );
		$validator        = tribe( 'tickets.rest-v1.validator' );
		$terms_controller = new WP_REST_Terms_Controller( Tribe__Tickets__Main::TAXONOMY );
		$archive_endpoint = new Tribe__Tickets__REST__V1__Endpoints__Archive_Category( $messages, $post_repository, $validator, $terms_controller );
		$single_endpoint  = new Tribe__Tickets__REST__V1__Endpoints__Single_Category( $messages, $post_repository, $validator, $terms_controller );

		tribe_singleton( 'tickets.rest-v1.endpoints.archive-category', $archive_endpoint );

		if ( $register_routes ) {
			$namespace = $this->get_events_route_namespace();

			register_rest_route(
				$namespace,
				'/categories',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $archive_endpoint, 'get' ),
						'args'     => $archive_endpoint->READ_args(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'args'                => $single_endpoint->CREATE_args(),
						'permission_callback' => array( $single_endpoint, 'can_create' ),
						'callback'            => array( $single_endpoint, 'create' ),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/categories/(?P<id>\\d+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $single_endpoint, 'get' ),
						'args'     => $single_endpoint->READ_args(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $single_endpoint->EDIT_args(),
						'permission_callback' => array( $single_endpoint, 'can_edit' ),
						'callback'            => array( $single_endpoint, 'update' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $single_endpoint->DELETE_args(),
						'permission_callback' => array( $single_endpoint, 'can_delete' ),
						'callback'            => array( $single_endpoint, 'delete' ),
					),
				)
			);
		}

		$documentation_endpoint = tribe( 'tickets.rest-v1.endpoints.documentation' );
		$documentation_endpoint->register_documentation_provider( '/categories', $archive_endpoint );
		$documentation_endpoint->register_documentation_provider( '/categories/{id}', $single_endpoint );
	}

	/**
	 * Builds and hooks the event tags archives endpoint
	 *
	 * @since 4.6
	 *
	 * @param bool $register_routes Whether routes for the endpoint should be registered or not.
	 */
	protected function register_tags_endpoint( $register_routes ) {
		$messages         = tribe( 'tickets.rest-v1.messages' );
		$post_repository  = tribe( 'tickets.rest-v1.repository' );
		$validator        = tribe( 'tickets.rest-v1.validator' );
		$terms_controller = new WP_REST_Terms_Controller( 'post_tag' );
		$archive_endpoint = new Tribe__Tickets__REST__V1__Endpoints__Archive_Tag( $messages, $post_repository, $validator, $terms_controller );
		$single_endpoint = new Tribe__Tickets__REST__V1__Endpoints__Single_Tag( $messages, $post_repository, $validator, $terms_controller );

		tribe_singleton( 'tickets.rest-v1.endpoints.archive-category', $archive_endpoint );

		if ( $register_routes ) {
			$namespace = $this->get_events_route_namespace();

			register_rest_route(
				$namespace,
				'/tags',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $archive_endpoint, 'get' ),
						'args'     => $archive_endpoint->READ_args(),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'args'                => $single_endpoint->CREATE_args(),
						'permission_callback' => array( $single_endpoint, 'can_create' ),
						'callback'            => array( $single_endpoint, 'create' ),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/tags/(?P<id>\\d+)',
				array(
					array(
						'methods'  => WP_REST_Server::READABLE,
						'callback' => array( $single_endpoint, 'get' ),
						'args'     => $single_endpoint->READ_args(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'args'                => $single_endpoint->EDIT_args(),
						'permission_callback' => array( $single_endpoint, 'can_edit' ),
						'callback'            => array( $single_endpoint, 'update' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'args'                => $single_endpoint->DELETE_args(),
						'permission_callback' => array( $single_endpoint, 'can_delete' ),
						'callback'            => array( $single_endpoint, 'delete' ),
					),
				)
			);
		}

		$documentation_endpoint = tribe( 'tickets.rest-v1.endpoints.documentation' );
		$documentation_endpoint->register_documentation_provider( '/tags', $archive_endpoint );
		$documentation_endpoint->register_documentation_provider( '/tags/{id}', $single_endpoint );
	}

}
