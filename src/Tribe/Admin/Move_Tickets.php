<?php
class Tribe__Tickets__Admin__Move_Tickets {
	protected $dialog_name = 'move_tickets';
	protected $ticket_history;
	protected $has_multiple_providers = false;
	protected $ticket_provider = '';

	/**
	 * The attendees currently being operated on.
	 *
	 * Structure is an array indexed by attendee ID, with each value being
	 * the attendee array itself.
	 *
	 * @var array
	 */
	protected $attendees = array();

	public function __construct() {
		$this->ticket_history();

		add_action( 'admin_init', array( $this, 'dialog' ) );
		add_action( 'tribe_events_tickets_attendees_table_bulk_actions', array( $this, 'bulk_actions' ) );
		add_action( 'wp_ajax_move_tickets', array( $this, 'move_tickets_request' ) );
		add_action( 'tribe_tickets_ticket_type_moved', array( $this, 'move_all_tickets_for_type' ), 10, 4 );
		add_action( 'wp_ajax_move_tickets_get_post_types', array( $this, 'get_post_types' ) );
		add_action( 'wp_ajax_move_tickets_get_post_choices', array( $this, 'get_post_choices' ) );
		add_action( 'wp_ajax_move_tickets_get_ticket_types', array( $this, 'get_ticket_types' ) );
	}

	/**
	 * @return Tribe__Tickets__Admin__Ticket_History
	 */
	public function ticket_history() {
		if ( ! isset( $this->ticket_history ) ) {
			$this->ticket_history = new Tribe__Tickets__Admin__Ticket_History;
		};

		return $this->ticket_history;
	}

	/**
	 * Sets up the move tickets dialog.
	 */
	public function dialog() {
		if ( ! $this->is_move_tickets_dialog() ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['check'], 'move_tickets' ) ) {
			return;
		}

		$event_id = isset( $_GET[ 'event_id' ] ) ? absint( $_GET[ 'event_id' ] ) : absint( $_GET[ 'post' ] );
		$attendee_ids = array_map( 'intval', explode( '|', @$_GET[ 'ticket_ids' ] ) );
		$this->build_attendee_list( $attendee_ids, $event_id );

		/**
		 * Provides an opportunity to modify the template variables used in the
		 * move tickets dialog.
		 *
		 * @param array $template_vars
		 */
		$template_vars = (array) apply_filters( 'tribe_tickets_move_tickets_template_vars', array(
			'title'              => __( 'Move Attendees', 'event-tickets' ),
			'mode'               => 'move_tickets',
			'check'              => wp_create_nonce( 'move_tickets' ),
			'event_name'         => get_the_title( $event_id ),
			'attendees'          => $this->attendees,
			'multiple_providers' => $this->has_multiple_providers,
		) );

		define( 'IFRAME_REQUEST', true );
		$this->dialog_assets();
		iframe_header( $template_vars[ 'title'] );

		extract( $template_vars );
		include EVENT_TICKETS_DIR . '/src/admin-views/move-tickets.php';

		iframe_footer();
		exit();
	}

	/**
	 * Enqueues all assets and data required by the move tickets dialog.
	 */
	protected function dialog_assets() {
		// Ensure common admin CSS is enqueued within this screen
		add_filter( 'tribe_asset_enqueue_tribe-common-admin', '__return_true', 20 );

		// @todo consider switching to tribe_asset() following resolution of https://github.com/moderntribe/tribe-common/pull/111#discussion_r68219366
		$script_url = Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/js/move-tickets-dialog.js';

		/**
		 * Provides an opportunity to modify the variables passed to the move
		 * tickets JS code.
		 *
		 * @param array $script_data
		 */
		$script_vars = apply_filters( 'tribe_tickets_move_tickets_script_data', array(
			'check' =>
				wp_create_nonce( 'move_tickets' ),
			'unexpected_failure' =>
				'<p>' . __( 'Woops! We could not complete the requested operation due to an unforeseen problem.', 'event-tickets' ) . '</p>',
			'update_post_list_failure' =>
				__( 'Unable to update the post list. Please refresh the page and try again.', 'event-tickets' ),
			'no_posts_found' =>
				__( 'No results found - you may need to widen your search criteria.', 'event-tickets' ),
			'no_ticket_types_found' =>
				__( 'No ticket types were found for this post.', 'event-tickets' ),
			'loading_msg' =>
				__( 'Loading, please wait&hellip;', 'event-tickets' ),
			'src_post_id' =>
				isset( $_GET[ 'event_id' ] ) ? absint( $_GET[ 'event_id' ] ) : absint( $_GET[ 'post' ] ),
			'ticket_ids' =>
				array_keys( $this->attendees ),
			'provider' =>
				$this->ticket_provider,
			'mode' =>
				'move_tickets',
		) );

		wp_enqueue_script( 'tribe-move-tickets-dialog', $script_url, array( 'jquery' ), false, true );
		wp_localize_script( 'tribe-move-tickets-dialog', 'tribe_move_tickets_data', $script_vars );
	}

	/**
	 * Indicates if the current request is for the "move tickets type"
	 * dialog or not.
	 *
	 * @return bool
	 */
	protected function is_move_tickets_dialog() {
		return ( isset( $_GET[ 'dialog' ] ) && $this->dialog_name === $_GET[ 'dialog' ] );
	}

	/**
	 * @return string
	 */
	public function dialog_name() {
		return $this->dialog_name;
	}

	/**
	 * Takes the provided array of attendee IDs and uses it to populate
	 * $this->attendees, to determine which ticket provider we're using
	 * or if the range of tickets includes more than one provider.
	 *
	 * @param array $ticket_ids
	 * @param int   $event_id
	 */
	protected function build_attendee_list( array $ticket_ids, $event_id ) {
		$this->attendees = array();
		$this->ticket_provider = '';
		$this->has_multiple_providers = false;

		foreach ( Tribe__Tickets__Tickets::get_event_attendees( $event_id ) as $attendee ) {
			$attendee_id = (int) $attendee[ 'attendee_id' ];

			if ( ! in_array( $attendee_id, $ticket_ids ) ) {
				continue;
			}

			$provider = $this->get_ticket_provider( $attendee );
			$this->attendees[ $attendee_id ] = $attendee;

			if ( ! empty( $this->ticket_provider ) && $this->ticket_provider !== $provider ) {
				$this->has_multiple_providers = true;
			}

			$this->ticket_provider = $provider;
		}
	}

	/**
	 * Given an attendee array, attempts to determine which ticket provider
	 * owns it (Woo, RSVP, etc).
	 *
	 * @param array $attendee
	 *
	 * @return string|null
	 */
	protected function get_ticket_provider( array $attendee ) {
		if ( ! isset( $attendee[ 'product_id' ] ) ) {
			return null;
		}

		$ticket_type = Tribe__Tickets__Tickets::load_ticket_object( $attendee[ 'product_id' ] );

		if ( ! $ticket_type ) {
			return null;
		}

		if ( property_exists( $ticket_type, 'provider_class' ) ) {
			return $ticket_type->provider_class;
		}

		return null;
	}

	/**
	 * Adds a "Move" option to the attendee screen bulk action selector.
	 *
	 * There is not a corresponding bulk action handler, as such, because when this
	 * is selected further handling will be managed via JS (and interaction will be
	 * through a modal interface).
	 *
	 * @param array $actions
	 *
	 * @return array
	 */
	public function bulk_actions( array $actions ) {
		$actions[ 'move' ] = _x( 'Move', 'attendee screen bulk actions', 'event-tickets' );
		return $actions;
	}

	/**
	 * Responds to ajax requests for a list of supported post types.
	 */
	public function get_post_types() {
		if ( ! wp_verify_nonce( $_POST['check' ], 'move_tickets' ) ) {
			wp_send_json_error();
		}

		wp_send_json_success( array( 'posts' => $this->get_post_types_list() ) );
		exit();
	}

	/**
	 * Returns a list of post types for which tickets are currently enabled.
	 *
	 * The list is expressed as an array in the following format:
	 *
	 *     [ 'slug' => 'name', ... ]
	 *
	 * @return array
	 */
	protected function get_post_types_list() {
		$types_list = array( 'all' => __( 'All supported types', 'tribe-tickets' ) );

		foreach ( Tribe__Tickets__Main::instance()->post_types() as $type ) {
			$pto = get_post_type_object( $type );
			$types_list[ $type ] = $pto->label;
		}

		return $types_list;
	}

	/**
	 * Responds to requests for a list of possible destination posts.
	 */
	public function get_post_choices() {
		if ( ! wp_verify_nonce( $_POST['check' ], 'move_tickets' ) ) {
			wp_send_json_error();
		}

		$args = wp_parse_args( $_POST, array(
			'post_type'    => '',
			'search_terms' => '',
			'ignore'       => '',
		) );

		wp_send_json_success( array( 'posts' =>  $this->get_possible_matches( $args ) ) );
		exit();
	}

	/**
	 * Returns a list of posts that could be possible homes for a ticket
	 * type, given the constraints in optional array $request (if not set,
	 * looks in $_POST for the corresponding values):
	 *
	 * - 'post_type': string or array of post types
	 * - 'search_term': string used for searching posts to narrow the field
	 *
	 * @param array|null $request post parameters (or looks at $_POST if not set)
	 *
	 * @return array
	 */
	protected function get_possible_matches( array $request = null ) {
		// Take the params from $request if set, else look at $_POST
		$params = wp_parse_args( is_null( $request ) ? $_POST : $request, array(
			'post_type' => array(),
			'search_terms' => '',
			'ignore' => '',
		) );

		// The post_type argument should be an array (of all possible types, if not specified)
		$post_types = array_filter( (array) $params[ 'post_type' ] );

		if ( empty( $post_types ) || 'all' === $params[ 'post_type' ] ) {
			$post_types = array_keys( $this->get_post_types_list() );
		}

		/**
		 * Controls the number of posts returned when searching for posts that
		 * can serve as ticket hosts.
		 *
		 * @param int $limit
		 */
		$limit = (int) apply_filters( 'tribe_tickets_find_ticket_type_host_posts_limit', 100 );

		$ignore_ids = is_numeric( $params[ 'ignore' ] ) ? array( absint( $params[ 'ignore' ] ) ) : array();

		return $this->format_post_list( get_posts( array(
			'post_type'      => $post_types,
			'posts_per_page' => $limit,
			'eventDisplay'   => 'custom',
			'orderby'        => 'title',
			'order'          => 'ASC',
			's'              => $params[ 'search_terms' ],
			'post__not_in'   => $ignore_ids,
		) ) );
	}

	/**
	 * Given an array of WP_Post objects, returns an array containing the post title
	 * of each (with the post ID as the index).
	 *
	 * @param array $query_results
	 *
	 * @return array
	 */
	protected function format_post_list( array $query_results ) {
		$posts = array();

		foreach ( $query_results as $wp_post ) {
			$title = $wp_post->post_title;

			// Append the event start date if there is one, ie for events
			if ( $wp_post->_EventStartDate ) {
				$title .= ' (' . tribe_get_start_date( $wp_post->ID ) . ')';
			}

			$posts[ $wp_post->ID ] = $title;
		}

		return $posts;
	}

	/**
	 * Returns a list of ticket types available in a specific post
	 * (belonging to a specific provider).
	 */
	public function get_ticket_types() {
		if ( ! wp_verify_nonce( $_POST['check' ], 'move_tickets' ) ) {
			wp_send_json_error();
		}

		$args = wp_parse_args( $_POST, array(
			'post_id'  => '',
			'provider' => '',
		) );

		wp_send_json_success( array( 'posts' =>  $this->get_ticket_type_matches( $args[ 'post_id' ], $args[ 'provider' ] ) ) );
		exit();
	}

	/**
	 * Builds a list of ticket types in the designated post and belonging to
	 * the specified provider.
	 *
	 * @param int    $target_post_id
	 * @param string $provider
	 *
	 * @return array
	 */
	protected function get_ticket_type_matches( $target_post_id, $provider ) {
		$ticket_types = array();

		foreach ( Tribe__Tickets__Tickets::get_event_tickets( $target_post_id ) as $ticket ) {
			if ( $provider !== $ticket->provider_class ) {
				continue;
			}

			$ticket_types[ absint( $ticket->ID ) ] = esc_html( $ticket->name );
		}

		return $ticket_types;
	}

	/**
	 * Listens for and handles requests to reassign tickets from one ticket type
	 * to another.
	 */
	public function move_tickets_request() {
		if ( ! wp_verify_nonce( $_POST['check' ], 'move_tickets' ) ) {
			wp_send_json_error();
		}

		$args = wp_parse_args( $_POST, array(
			'ticket_ids'     => '',
			'target_type_id' => '',
			'src_post_id'    => '',
			'target_post_id' => ''
		) );

		$src_post_id    = absint( $args[ 'src_post_id' ] );
		$ticket_ids     = array_map( 'intval', (array) $args[ 'ticket_ids' ] );
		$target_type_id = absint( $args[ 'target_type_id' ] );
		$target_post_id = absint( $args[ 'target_post_id' ] );

		if ( ! $ticket_ids || ! $target_type_id ) {
			wp_send_json_error( array(
				'message' => __( 'Tickets could not be moved: valid ticket IDs or a destination ID were not provided.', 'event-tickets' )
			) );
		}

		if ( ! $this->move_tickets( $ticket_ids, $target_type_id, $src_post_id, $target_post_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Tickets could not be moved: there was an unexpected failure during reassignment.', 'event-tickets' )
			) );
		}

		$redirect_url = add_query_arg( array(
			'event_id' => absint( $args[ 'src_post_id' ] ),
			'action'   => 'edit',
			'page'     => 'tickets-attendees'
		),
			get_admin_url( null, 'post.php' )
		);

		wp_send_json_success( array(
			'message' => sprintf(
				__( 'The tickets were successfully moved and can be found within %1$sthis post%2$s. Please wait a moment while we refresh the editor screen.', 'event-tickets' ),
				'<a href="' . esc_url( get_admin_url( null, '/post.php?post=' . $args[ 'target_post_id' ] . '&action=edit' ) ) . '" target="_blank">',
				'</a>'
			),
			'redirect_top' => $redirect_url,
		) );

		exit();
	}

	/**
	 * Moves tickets to a new ticket type.
	 *
	 * The target ticket type *must* belong to the same provider as the tickets being
	 * moved (ie, you cannot move RSVP tickets to a WooCommerce ticket type, nor can
	 * a mix of RSVP and WooCommerce tickets be moved to a new ticket type).
	 *
	 * @param array $ticket_ids
	 * @param int   $tgt_ticket_type_id
	 * @param int   $src_event_id
	 * @param int   $tgt_event_id
	 *
	 * @return bool
	 */
	public function move_tickets( array $ticket_ids, $tgt_ticket_type_id, $src_event_id, $tgt_event_id ) {
		$instigator_id = get_current_user_id();
		$ticket_type   = Tribe__Tickets__Tickets::load_ticket_object( $tgt_ticket_type_id );

		if ( ! $ticket_type ) {
			return false;
		}

		$ticket_objects = array();
		$providers = array();

		foreach ( Tribe__Tickets__Tickets::get_event_attendees( $src_event_id ) as $issued_ticket ) {
			if ( in_array( absint( $issued_ticket[ 'attendee_id' ] ), $ticket_ids ) ) {
				$ticket_objects[] = $issued_ticket;
				$providers[ $issued_ticket[ 'provider' ] ] = true;
			}
		}

		// We expect to have found as many tickets as were specified
		if ( count( $ticket_objects ) !== count( $ticket_ids ) ) {
			return false;
		}

		// Check that the tickets are homogenous in relation to the ticket provider
		if ( 1 !== count( $providers ) ) {
			return false;
		}

		$provider_class   = key( $providers );
		$ticket_type_key  = constant( $provider_class . '::ATTENDEE_PRODUCT_KEY' );
		$ticket_event_key = constant( $provider_class . '::ATTENDEE_EVENT_KEY' );

		if ( empty( $ticket_type_key ) || empty( $ticket_event_key ) ) {
			return false;
		}

		foreach ( $ticket_objects as $single_ticket ) {
			$ticket_id = $single_ticket[ 'attendee_id' ];
			$current_ticket_type_id = get_post_meta( $ticket_id, $ticket_type_key, true );

			update_post_meta( $ticket_id, $ticket_type_key, $tgt_ticket_type_id );
			update_post_meta( $ticket_id, $ticket_event_key, $tgt_event_id );

			Tribe__Post_History::load( $ticket_id )->add_entry( sprintf(
				__( 'Moved from ticket type %1$d (in post %2$d) to ticket type %3$d (in post %4$d) by user %5$d', 'event-tickets' ),
				$current_ticket_type_id,
				$src_event_id,
				$tgt_ticket_type_id,
				$tgt_event_id,
				$instigator_id
			) );
		}

		return true;
	}

	/**
	 * When a ticket type is moved, the tickets need to move with it. This callback takes
	 * care of that process.
	 *
	 * @see Tribe__Tickets__Admin__Move_Ticket_Types::move_ticket_type()
	 *
	 * @param int $ticket_type_id
	 * @param int $destination_post_id
	 * @param int $src_post_id
	 * @param int $instigator_id
	 */
	public function move_all_tickets_for_type( $ticket_type_id, $destination_post_id, $src_post_id, $instigator_id ) {
		foreach (  Tribe__Tickets__Tickets::get_event_attendees( $src_post_id ) as $issued_ticket ) {
			// We're only interested in tickets of the specified type
			if ( (int) $ticket_type_id !== (int) $issued_ticket[ 'product_id' ] ) {
				continue;
			}

			if ( ! class_exists( $issued_ticket[ 'provider' ] ) ) {
				continue;
			}

			$issued_ticket_id = $issued_ticket[ 'attendee_id' ];

			// Move the ticket to the destination post
			$event_key = constant( $issued_ticket[ 'provider' ] . '::ATTENDEE_EVENT_KEY' );
			update_post_meta( $issued_ticket_id, $event_key, $destination_post_id );

			// Maintain an audit trail
			$audit_trail_msg = sprintf(
				__( 'Ticket was moved to post %1$d from post %2$d by user %3$d', 'event-tickets' ),
				$destination_post_id,
				$src_post_id,
				$instigator_id
			);

			Tribe__Post_History::load( $issued_ticket_id )->add_entry( $audit_trail_msg );
		}
	}
}