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
	 * This prefix is appended to the `The Events Calendar` REST API URL ones.
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
		add_filter( 'tribe_events_archive_get_args', [ $this, 'parse_events_rest_args' ], 10, 3 );

		add_filter( 'tribe_rest_event_data', [ $this, 'rest_event_data_add_attendance' ], 10, 2 );
		add_filter( 'tribe_rest_events_archive_data', [ $this, 'rest_events_archive_add_attendance' ], 10, 2 );

		add_filter( 'tec_tickets_rest_api_archive_results', [ $this, 'filter_out_tickets_on_unauthorized' ], 100, 2 );
		add_filter( 'tribe_rest_single_ticket_data', [ $this, 'filter_out_single_ticket_data_on_unauthorized' ], 100, 2 );
	}

	/**
	 * Filters out single ticket data that unauthorized users should not see.
	 *
	 * @since 5.17.0.1
	 *
	 * @param array $ticket_data
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	public function filter_out_single_ticket_data_on_unauthorized( array $ticket_data, WP_REST_Request $request ): array {
		if ( $this->request_has_manage_access() ) {
			return $ticket_data;
		}

		$ticket_validator = tribe( 'tickets.rest-v1.validator' );

		if ( $ticket_validator->should_see_ticket( $ticket_data['post_id'] ?? 0, $request ) ) {
			return $ticket_data;
		}

		return $ticket_validator->remove_ticket_data( $ticket_data );
	}
	/**
	 * Filters out tickets that unauthorized users should not see.
	 *
	 * @since 5.17.0.1
	 *
	 * @param array           $tickets The tickets to filter.
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return array The filtered tickets.
	 */
	public function filter_out_tickets_on_unauthorized( array $tickets, WP_REST_Request $request ) : array {
		if ( $this->request_has_manage_access() ) {
			return $tickets;
		}

		$ticket_validator = tribe( 'tickets.rest-v1.validator' );

		foreach ( $tickets as $offset => $ticket ) {
			if ( $ticket_validator->should_see_ticket( $ticket['post_id'] ?? 0, $request ) ) {
				continue;
			}

			$tickets[ $offset ] = $ticket_validator->remove_ticket_data( $ticket );
		}

		return $tickets;
	}

	/**
	 * Filters the data that will be returned for the events endpoint, adding attendance.
	 *
	 * @since 5.5.2
	 *
	 * @param array           $data    The retrieved data.
	 * @param WP_REST_Request $request The original request.
	 *
	 * @return array          $data    The retrieved data, updated with attendance if the request has access.
	 */
	public function rest_events_archive_add_attendance( $data, $request ) : array {

		if ( ! $this->request_has_manage_access() ) {
			return $data;
		}

		if ( empty( $data['events'] ) ) {
			return $data;
		}

		foreach ( $data['events'] as $event ) {
			$event_id       = is_array( $event ) ? $event['id'] : $event->id;
			$attendee_count = Tribe__Tickets__Tickets::get_event_attendees_count( $event_id );
			$checked_in     = Tribe__Tickets__Tickets::get_event_checkedin_attendees_count( $event_id );

			$event['attendance'] = [
				'total_attendees' => $attendee_count,
				'checked_in'      => $checked_in,
				'not_checked_in'  => $attendee_count - $checked_in,
			];

		}

		return $data;
	}

	/**
	 * Filters the data that will be returned for a single event, adding attendance.
	 *
	 * @since 5.5.2
	 *
	 * @param array   $data  The data that will be returned in the response.
	 * @param WP_Post $event The requested event.
	 *
	 * @return array  $data  The retrieved data, updated with attendance if the request has access.
	 */
	public function rest_event_data_add_attendance( $data, $event ) : array {

		if ( ! $this->request_has_manage_access() ) {
			return $data;
		}

		$post_id        = $event->ID;
		$attendee_count = Tribe__Tickets__Tickets::get_event_attendees_count( $post_id );
		$checked_in     = Tribe__Tickets__Tickets::get_event_checkedin_attendees_count( $post_id );

		$data['attendance'] = [
			'total_attendees' => $attendee_count,
			'checked_in'      => $checked_in,
			'not_checked_in'  => $attendee_count - $checked_in,
		];

		return $data;
	}

	/**
	 * Filter the args for Event Query over REST API.
	 *
	 * @since 5.5.0
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
	 * @since 5.5.0
	 *
	 * @return bool True if the request has access to private information, false otherwise.
	 */
	public function request_has_manage_access() : bool {
		return $this->user_has_manage_access() || $this->request_has_valid_api_key();
	}

	/**
	 * Return if the user has manage access.
	 *
	 * @since 5.5.0
	 *
	 * @return bool True if the user has manage access, false otherwise.
	 */
	public function user_has_manage_access() : bool {
		return current_user_can( 'edit_users' ) || current_user_can( 'tribe_manage_attendees' );
	}

	/**
	 * Return if user can read private information.
	 *
	 * @since 5.5.0
	 *
	 * @return bool True if user can read private information, false otherwise.
	 */
	public function user_has_read_private_posts_access() : bool {
		return current_user_can( 'read_private_posts' );
	}

	/**
	 * Return if the request has a valid api key.
	 *
	 * @since 5.5.0
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
