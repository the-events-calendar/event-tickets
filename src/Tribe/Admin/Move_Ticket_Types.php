<?php
class Tribe__Tickets__Admin__Move_Ticket_Types {
	const DIALOG_NAME = 'move_ticket_types';

	protected $screen;


	public function __construct() {
		add_action( 'admin_init', array( $this, 'dialog' ) );
		add_action( 'wp_ajax_move_ticket_types_post_list', array( $this, 'update_post_choices' ) );
		add_action( 'wp_ajax_move_ticket_type', array( $this, 'move_ticket_type_requests' ) );
		add_action( 'tribe_tickets_ticket_type_moved', array( $this, 'notify_attendees' ), 100, 3 );
		add_action( 'tribe_events_tickets_metabox_advanced', array( $this, 'expose_ticket_history' ), 100 );
	}

	public function dialog() {
		if ( ! isset( $_GET[ 'dialog' ] ) || self::DIALOG_NAME !== $_GET[ 'dialog' ] ) {
			return;
		}

		if ( ! wp_verify_nonce( $_GET['check'], 'move_ticket_type' ) ) {
			wp_die( __( 'You do not have permission to view this screen or else you may have followed an expired link. Please refresh the screen and try again.', 'event-tickets' ) );
		}

		if ( ! isset( $_GET[ 'post' ] ) && ! isset( $_POST[ 'post' ] ) ) {
			return;
		}

		$this->dialog_assets();
		$post_types = $this->get_post_types_list();
		$post_choices = $this->get_possible_matches( array(
			'ignore' => $_GET[ 'post' ],
		) );

		define( 'IFRAME_REQUEST', true );
		iframe_header( __( 'Move Ticket Type', 'event-tickets' ) );
		include EVENT_TICKETS_DIR . '/src/admin-views/move-ticket-type.php';
		iframe_footer();

		exit();
	}

	protected function dialog_assets() {
		// Ensure common admin CSS is enqueued within this screen
		add_filter( 'tribe_asset_enqueue_tribe-common-admin', '__return_true', 20 );

		// @todo consider switching to tribe_asset() following resolution of https://github.com/moderntribe/tribe-common/pull/111#discussion_r68219366
		$script_url = Tribe__Tickets__Main::instance()->plugin_url . 'src/resources/js/move-ticket-type-dialog.js';

		wp_enqueue_script( 'tribe-move-ticket-type-dialog', $script_url, array( 'jquery' ), false, true );
		wp_localize_script( 'tribe-move-ticket-type-dialog', 'tribe_move_tickets_data', array(
			'check' =>
				wp_create_nonce( 'move_ticket_type' ),
			'update_post_list_failure' =>
				__( 'Unable to update the post list. Please refresh the page and try again.', 'event-tickets' ),
			'nothing_found' =>
				__( 'No results found - you may need to widen your search criteria.', 'event-tickets' ),
			'unexpected_failure' =>
				__( 'Moving the ticket type failed unexpectedly. Please close the dialog or refresh the page and try again.', 'event-tickets' ),
			'ticket_type_id' =>
				absint( $_GET[ 'ticket_type_id' ] ),
			'src_post_id' =>
				absint( $_GET[ 'post' ] ),
		) );
	}

	/**
	 * Returns an updated list of post choices.
	 */
	public function update_post_choices() {
		if ( ! wp_verify_nonce( $_POST['check' ], 'move_ticket_type' ) ) {
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
		$post_types = (array) $params[ 'post_type' ];

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
	 * Listens out for ajax requests to move a ticket type to a new post.
	 */
	public function move_ticket_type_requests() {
		if ( ! wp_verify_nonce( $_POST['check' ], 'move_ticket_type' ) ) {
			wp_send_json_error();
		}

		$ticket_type_id = absint( $_POST[ 'type_id' ] );
		$destination_id = absint( $_POST[ 'destination' ] );

		if ( ! $ticket_type_id || ! $destination_id ) {
			wp_send_json_error( array(
				'message' => __( 'Ticket type could not be moved: the ticket type or destination post was invalid.', 'event-tickets' )
			) );
		}

		if ( ! $this->move_ticket_type( $ticket_type_id, $destination_id ) ) {
			wp_send_json_error( array(
				'message' => __( 'Ticket type could not be moved: unexpected failure during reassignment.', 'event-tickets' )
			) );
		}

		$redirect_url = add_query_arg( array(
				'post'   => absint( $_POST[ 'src_post_id' ] ),
				'action' => 'edit',
			),
			get_admin_url( null, 'post.php' )
		);

		wp_send_json_success( array(
			'message' => sprintf(
				__( 'The ticket type was successfully moved to %1$sthis post%2$s. Please wait a moment while we refresh the editor screen.', 'event-tickets' ),
				'<a href="' . esc_url( get_admin_url( null, '/post.php?post=' . $destination_id . '&action=edit' ) ) . '" target="_blank">',
				'</a>'
			),
			'redirect_top' => $redirect_url,
		) );

		exit();
	}

	/**
	 * Moves a ticket type to a new post.
	 *
	 * For example, a VIP ticket type may be attached to an event called "Tuesday Race Day",
	 * however if this event is rained out it may be desirable to move the VIP ticket type
	 * to a new "Thursday Race Day" event instead.
	 *
	 * Moving the ticket type, rather than recreating it, can be useful when ticket orders
	 * have already been placed.
	 *
	 * @param int $ticket_type_id
	 * @param int $destination_post_id
	 * @param int $instigator_id (optional) the user who initiated or requested the change
	 *
	 * @return bool
	 */
	public function move_ticket_type( $ticket_type_id, $destination_post_id, $instigator_id = null ) {
		if ( null === $instigator_id ) {
			$instigator_id = get_current_user_id();
		}

		$ticket_type = Tribe__Tickets__Tickets::load_ticket_object( $ticket_type_id );

		if ( null === $ticket_type ) {
			return false;
		}

		$provider = $ticket_type->get_provider();
		$event_key = $provider->get_event_key();

		$src_post_id = get_post_meta( $ticket_type_id, $event_key, true );
		$success = update_post_meta( $ticket_type_id, $event_key, $destination_post_id );

		if ( ! $success ) {
			return false;
		}

		$audit_trail_msg = sprintf(
			__( 'Ticket type was moved to post %1$d from post %2$d by user %3$d', 'event-tickets' ),
			$destination_post_id,
			$src_post_id,
			$instigator_id
		);

		Tribe__Post_History::load( $ticket_type_id )->add_entry( $audit_trail_msg );

		/**
		 * Fires when a ticket type is relocated from one post to another.
		 *
		 * @param int $ticket_type_id       the ticket type which has been moved
		 * @param int $destination_post_id  the post to which the ticket type has been moved
		 * @param int $src_post_id          the post which previously hosted the ticket type
		 * @param int $instigator_id        the user who initiated the change
		 */
		do_action( 'tribe_tickets_ticket_type_moved', $ticket_type_id, $destination_post_id, $src_post_id, $instigator_id );

		return true;
	}

	/**
	 * Notify attendees who purchased tickets of this type that it has been
	 * reassigned to a different post/event.
	 *
	 * @param int $ticket_type_id
	 * @param int $new_post_id
	 * @param int $original_post_id
	 */
	public function notify_attendees( $ticket_type_id, $new_post_id, $original_post_id ) {
		$to_notify = array();

		// Build a list of email addresses we want to send notifications of the change to
		foreach ( Tribe__Tickets__Tickets::get_event_attendees( $new_post_id ) as $attendee ) {
			// We're not interested in attendees who were already attending this event
			if ( (int) $ticket_type_id !== (int) $attendee[ 'product_id' ] ) {
				continue;
			}

			// Skip if an email address isn't available
			if ( ! isset( $attendee[ 'purchaser_email' ] ) ) {
				continue;
			}

			if ( ! isset( $to_notify[ $attendee[ 'purchaser_email' ] ] ) ) {
				$to_notify[ $attendee[ 'purchaser_email' ] ] = 1;
			} else {
				$to_notify[ $attendee[ 'purchaser_email' ] ]++;
			}
		}

		// Dispatch the emails
		foreach ( $to_notify as $email_addr => $num_tickets ) {
			$to = apply_filters( 'tribe_tickets_ticket_type_moved_email_recipient', $email_addr );
			$attachments = apply_filters( 'tribe_tickets_ticket_type_moved_email_attachments', array() );

			$content = apply_filters( 'tribe_tickets_ticket_type_moved_email_content',
				$this->generate_email_content( $ticket_type_id, $new_post_id, $original_post_id, $num_tickets )
			);

			$headers = apply_filters( 'tribe_tickets_ticket_type_moved_email_headers',
				array( 'Content-type: text/html' )
			);

			$subject = apply_filters( 'tribe_tickets_ticket_type_moved_email_subject',
				sprintf( __( 'Changes to your tickets from %s', 'event-tickets' ), get_bloginfo( 'name' ) )
			);

			wp_mail( $to, $subject, $content, $headers, $attachments );
		}
	}

	/**
	 * @param int $ticket_type_id
	 * @param int $new_post_id
	 * @param int $original_post_id
	 * @param int $num_tickets
	 *
	 * @return string|void
	 */
	protected function generate_email_content( $ticket_type_id, $new_post_id, $original_post_id, $num_tickets ) {
		$vars = array(
			'original_event_id'   => $original_post_id,
			'original_event_name' => get_the_title( $original_post_id ),
			'new_event_id'        => $new_post_id,
			'new_event_name'      => get_the_title( $new_post_id ),
			'ticket_type_id'      => $ticket_type_id,
			'ticket_type_name'    => get_the_title( $ticket_type_id ),
			'num_tickets'         => $num_tickets
		);

		return tribe_tickets_get_template_part( 'tickets/email-ticket-type-moved', null, $vars, false );
	}

	/**
	 * Prints out the audit trail/post history for the current ticket, if
	 * available.
	 */
	public function expose_ticket_history() {
		// This will only be available during edit requests for existing tickets
		$ticket_id = @$_POST[ 'ticket_id' ];
		$ticket_object = Tribe__Tickets__Tickets::load_ticket_object( $ticket_id );

		if ( ! $ticket_object || ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return;
		}

		/**
		 * Allows the display of a ticket type's post history ("audit trail")
		 * within the ticket editor to be turned on or off.
		 *
		 * @param bool $show_history
		 */
		if ( ! apply_filters( 'tribe_tickets_show_ticket_type_history_in_ticket_editor', true ) ) {
			return;
		}

		// $provider is needed to form the correct table row classes (otherwise the
		// history section will not show/hide appropriately)
		$provider = $ticket_object->provider_class;
		$history = Tribe__Post_History::load( $ticket_id );

		if ( ! $history->has_entries() ) {
			return;
		}

		include EVENT_TICKETS_DIR . '/src/admin-views/ticket-type-history.php';
	}
}