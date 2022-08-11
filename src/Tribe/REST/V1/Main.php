<?php


/**
 * Class Tribe__Tickets__REST__V1__Main
 *
 * The main entry point for ET REST API.
 *
 * This class should not contain business logic and merely set up and start the ET REST API support.
 */
class Tribe__Tickets__REST__V1__Main extends Tribe__REST__Main {

	/**
	 * Event Tickets REST API URL prefix.
	 *
	 * This prefx is appended to the Modern Tribe REST API URL ones.
	 *
	 * @var string
	 */
	protected $url_prefix = '/tickets/v1';

	/**
	 * @var array
	 */
	protected $registered_endpoints = [];

	/**
	 * Hooks the filters and actions required for the REST API support to kick in.
	 *
	 * @since 4.7.5
	 */
	public function hook() {
		$this->hook_headers();
		$this->hook_settings();

		/** @var Tribe__Tickets__REST__V1__System $system */
		$system = tribe( 'tickets.rest-v1.system' );

		if ( ! $system->supports_et_rest_api() || ! $system->et_rest_api_is_enabled() ) {
			return;
		}

		// Add support for `ticketed` param on tribe_events filter on REST API.
		add_filter( 'tribe_events_archive_get_args', [ $this , 'parse_events_rest_args' ], 10, 3 );
	}

	/**
	 * Filter the args for Event Query over REST API.
	 *
	 * @since TBD
	 *
	 * @param array           $args Arguments used to get the events from the archive page.
	 * @param array           $data Array with the data to be returned to the REST response.
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return mixed
	 */
	public function parse_events_rest_args( $args, $data, $request ) {
		if ( isset( $request['ticketed'] ) ) {
			$args['has_rsvp_or_tickets'] = tribe_is_truthy( $request['ticketed'] );
		}

		return $args;
	}

	/**
	 * Hooks the additional headers and meta tags related to the REST API.
	 *
	 * @since 4.7.5
	 *
	 */
	protected function hook_headers() {
		/** @var Tribe__Tickets__REST__V1__System $system */
		$system = tribe( 'tickets.rest-v1.system' );
		/** @var Tribe__REST__Headers__Base_Interface $headers_base */
		$headers_base = tribe( 'tickets.rest-v1.headers-base' );

		if ( ! $system->et_rest_api_is_enabled() ) {
			if ( ! $system->supports_et_rest_api() ) {
				tribe_singleton( 'tickets.rest-v1.headers', new Tribe__REST__Headers__Unsupported( $headers_base, $this ) );
			} else {
				tribe_singleton( 'tickets.rest-v1.headers', new Tribe__REST__Headers__Disabled( $headers_base ) );
			}
		} else {
			tribe_singleton( 'tickets.rest-v1.headers', new Tribe__REST__Headers__Supported( $headers_base, $this ) );
		}

		add_action( 'wp_head', tribe_callback( 'tickets.rest-v1.headers', 'add_header' ) );
		add_action( 'template_redirect', tribe_callback( 'tickets.rest-v1.headers', 'send_header' ), 11 );
	}

	/**
	 * Hooks the additional Event Tickets Settings related to the REST API.
	 *
	 * @since 4.7.5
	 *
	 */
	protected function hook_settings() {
		add_filter( 'tribe_addons_tab_fields', tribe_callback( 'tickets.rest-v1.settings', 'filter_tribe_addons_tab_fields' ) );
	}

	/**
	 * Returns the URL where the API users will find the API documentation.
	 *
	 * @since 4.7.5
	 *
	 * @return string
	 */
	public function get_reference_url() {
		return esc_url( 'https://theeventscalendar.com/' );
	}

	/**
	 * Returns the semantic version for REST API
	 *
	 * @since 4.7.5
	 *
	 * @return string
	 */
	public function get_semantic_version() {
		return '1.0.0';
	}

	/**
	 * Returns the events REST API namespace string that should be used to register a route.
	 *
	 * @since 4.7.5
	 *
	 * @return string
	 */
	public function get_events_route_namespace() {
		return $this->get_namespace() . '/tickets/' . $this->get_version();
	}

	/**
	 * Returns the string indicating the REST API version.
	 *
	 * @since 4.7.5
	 *
	 * @return string
	 */
	public function get_version() {
		return 'v1';
	}

	/**
	 * Returns the REST API URL prefix that will be appended to the namespace.
	 *
	 * The prefix should be in the `/some/path` format.
	 *
	 * @since 4.7.5
	 *
	 * @return string
	 */
	protected function url_prefix() {
		return $this->url_prefix;
	}

	/**
	 * Return if the request has access to private information.
	 *
	 * @since TBD
	 *
	 * @return bool True if the request has access to private information, false otherwise.
	 */
	public function request_has_manage_access() : bool {
		return $this->user_has_manage_access() || $this->request_has_valid_api_key();
	}

	/**
	 * Return if the user has manage access.
	 *
	 * @since TBD
	 *
	 * @return bool True if the user has manage access, false otherwise.
	 */
	public function user_has_manage_access() : bool {
		return current_user_can( 'edit_users' ) || current_user_can( 'tribe_manage_attendees' );
	}

	/**
	 * Return if user can read private information.
	 *
	 * @since TBD
	 *
	 * @return bool True if user can read private information, false otherwise.
	 */
	public function user_has_read_private_posts_access() : bool {
		return current_user_can( 'read_private_posts' );
	}

	/**
	 * Return if the request has a valid api key.
	 *
	 * @since TBD
	 *
	 * @return bool True if the request has a valid api key, false otherwise.
	 */
	public function request_has_valid_api_key() : bool {
		$option = tribe_get_option( 'tickets-plus-qr-options-api-key', '' );

		if ( empty( $option ) ) {
			return false;
		}

		$request_var = tribe_get_request_var( 'api_key' );

		if ( empty( $request_var ) || $option !== $request_var ) {
			return false;
		}

		return true;
	}

}
