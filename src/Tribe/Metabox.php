<?php

/**
 *    Class in charge of registering and displaying
 *  the tickets metabox in the event edit screen.
 *  Metabox will only be added if there's a
 *     Tickets Pro provider (child of TribeTickets)
 *     available.
 */
class Tribe__Tickets__Metabox {

	/**
	 * Configure all action and filters user by this Class
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'add_meta_boxes', array( $this, 'configure' ) );


		add_filter( 'tribe_events_tickets_ajax_ticket_edit', array( $this, 'ajax_ticket_edit_controls' ) );

		add_action( 'wp_ajax_tribe-ticket-panels', array( $this, 'ajax_panels' ) );

		add_action( 'wp_ajax_tribe-ticket-add', array( $this, 'ajax_ticket_add' ) );
		add_action( 'wp_ajax_tribe-ticket-delete', array( $this, 'ajax_ticket_delete' ) );
		add_action( 'wp_ajax_tribe-ticket-edit', array( $this, 'ajax_ticket_edit' ) );
		add_action( 'wp_ajax_tribe-ticket-checkin', array( $this, 'ajax_attendee_checkin' ) );
		add_action( 'wp_ajax_tribe-ticket-uncheckin', array( $this, 'ajax_attendee_uncheckin' ) );
	}

	/**
	 * Configures the Tickets Editor into a Post Type
	 *
	 * @since  TBD
	 *
	 * @param  string $post_type Which post type we are trying to configure
	 *
	 * @return void
	 */
	public function configure( $post_type = null ) {
		$modules = Tribe__Tickets__Tickets::modules();
		if ( empty( $modules ) ) {
			return;
		}

		if ( ! in_array( $post_type, Tribe__Tickets__Main::instance()->post_types() ) ) {
			return;
		}

		add_meta_box(
			'tribetickets',
			esc_html__( 'Tickets', 'event-tickets' ),
			array( $this, 'render' ),
			$post_type,
			'normal',
			'high'
		);

		// If we get here means that we will need Thickbox
		add_thickbox();
	}

	/**
	 * Render the actual Metabox
	 *
	 * @since  TBD
	 *
	 * @param  int   $post_id  Which post we are dealing with
	 *
	 * @return string|bool
	 */
	public function render( $post_id ) {
		$modules = Tribe__Tickets__Tickets::modules();
		if ( empty( $modules ) ) {
			return false;
		}

		$post = get_post( $post_id );

		// Prepare all the variables required
		$start_date = date( 'Y-m-d H:00:00' );
		$end_date   = date( 'Y-m-d H:00:00' );
		$start_time = Tribe__Date_Utils::time_only( $start_date, false );
		$end_time   = Tribe__Date_Utils::time_only( $start_date, false );

		$show_global_stock = Tribe__Tickets__Tickets::global_stock_available();
		$tickets           = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );
		$global_stock      = new Tribe__Tickets__Global_Stock( $post->ID );

		return tribe( 'tickets.admin.views' )->template( array( 'editor', 'metabox' ), get_defined_vars() );
	}

	/**
	 * Refreshes panels after ajax calls that change data
	 *
	 * @since 4.6
	 *
	 * @return string html content of the panels
	 */
	public function ajax_panels() {
		$post_id = absint( tribe_get_request_var( 'post_id', 0 ) );

		// Didn't get a post id to work with - bail
		if ( ! $post_id ) {
			wp_send_json_error( esc_html__( 'Invalid Post ID', 'event-tickets' ) );
		}

		// Overwrites for a few templates that use get_the_ID() and get_post()
		global $post;

		$post = get_post( $post_id );
		$data = wp_parse_args( tribe_get_request_var( array( 'data' ), array() ), array() );
		$notice = tribe_get_request_var( 'tribe-notice', false );

		$data = Tribe__Utils__Array::get( $data, array( 'tribe-tickets' ), null );

		// Save if the info was passed
		if ( ! empty( $data ) ) {
			tribe( 'tickets.handler' )->save_order( $post->ID, isset( $data['list'] ) ? $data['list'] : null );
			tribe( 'tickets.handler' )->save_settings( $post->ID, isset( $data['settings'] ) ? $data['settings'] : null );
		}

		$return['notice'] = $this->notice( $notice );

		$return = array_merge( $return, $this->get_panels( $post ) );

		/**
		 * Allows filtering the data by other plugins/ecommerce solutions©
		 *
		 * @since 4.6
		 *
		 * @param array the return data
		 * @param int the post/event id
		 */
		$return = apply_filters( 'tribe_tickets_ajax_refresh_tables', $return, $post->ID );

		wp_send_json_success( $return );
	}

	public function get_panels( $post, $ticket = null ) {
		if ( ! $post instanceof WP_Post ) {
			$post = get_post( $post );
		}

		// Bail on Invalid post
		if ( ! $post instanceof WP_Post ) {
			return array();
		}

		// Overwrites for a few templates that use get_the_ID() and get_post()
		$GLOBALS['post'] = $post;

		// Let's create tickets list markup to return
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post->ID );

		$panels = array(
			'list' => tribe( 'tickets.admin.views' )->template( 'editor/panel/list', array( 'post_id' => $post->ID, 'tickets' => $tickets ), false ),
			'settings' => tribe( 'tickets.admin.views' )->template( 'editor/panel/settings', array( 'post_id' => $post->ID ), false ),
			'ticket' => tribe( 'tickets.admin.views' )->template( 'editor/panel/ticket', array( 'post_id' => $post->ID, 'ticket_id' => $ticket ), false ),
		);

		return $panels;
	}

	/**
	 * Sanitizes the data for the new/edit ticket ajax call,
	 * and calls the child save_ticket function.
	 */
	public function ajax_ticket_add() {
		$post_id = absint( tribe_get_request_var( 'post_id', 0 ) );

		if ( ! isset( $_POST['post_id'] ) ) {
			wp_send_json_error( esc_html__( 'Invalid parent Post', 'event-tickets' ) );
		}

		/**
		 * This is needed because a provider can implement a dynamic set of fields.
		 * Each provider is responsible for sanitizing these values.
		 */
		$data = wp_parse_args( tribe_get_request_var( array( 'data' ), array() ), array() );

		if ( ! $this->has_permission( $post_id, $_POST, 'add_ticket_nonce' ) ) {
			wp_send_json_error( esc_html__( 'Failed to Add the Ticket, Refresh the Page to try again.', 'event-tickets' ) );
		}

		if ( ! isset( $data['ticket_provider'] ) || ! $this->module_is_valid( $data['ticket_provider'] ) ) {
			wp_send_json_error( esc_html__( 'Commerce Module invalid', 'event-tickets' ) );
		}

		// Get the Module
		$module = call_user_func( array( $data['ticket_provider'], 'get_instance' ) );

		// Do the actual adding
		$ticket_id = $module->ticket_add( $post_id, $data );

		// Successful?
		if ( $ticket_id ) {
			/**
			 * Fire action when a ticket has been added
			 *
			 * @param int $post_id ID of parent "event" post
			 */
			do_action( 'tribe_tickets_ticket_added', $post_id );
		} else {
			wp_send_json_error( esc_html__( 'Failed to Add the Ticket', 'event-tickets' ) );
		}

		$return['notice'] = $this->notice( 'ticket-add' );
		$return = $this->get_panels( $post_id );

		/**
		 * Filters the return data for ticket add
		 *
		 * @param array $return Array of data to return to the ajax call
		 * @param int $post_id ID of parent "event" post
		 */
		$return = apply_filters( 'event_tickets_ajax_ticket_add_data', $return, $post_id );

		wp_send_json_success( $return );
	}

	/**
	 * Handles the check-in ajax call, and calls the checkin method.
	 *
	 * @todo use of 'order_id' in this method is misleading (we're working with the attendee id)
	 *       we should consider revising in a back-compat minded way
	 */
	public function ajax_handler_attendee_checkin() {

		if ( ! isset( $_POST['order_ID'] ) || intval( $_POST['order_ID'] ) == 0 ) {
			wp_send_json_error( 'Bad post' );
		}

		if ( ! isset( $_POST['provider'] ) || ! $this->module_is_valid( $_POST['provider'] ) ) {
			wp_send_json_error( 'Bad module' );
		}

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'checkin' ) || ! $this->user_can( 'edit_posts', $_POST['order_ID'] ) ) {
			wp_send_json_error( "Cheatin' huh?" );
		}

		$order_id = $_POST['order_ID'];

		// Pass the control to the child object
		$did_checkin = $this->checkin( $order_id );

		$this->maybe_update_attendees_cache( $did_checkin );

		wp_send_json_success( $did_checkin );
	}

	/**
	 * Handles the check-in ajax call, and calls the uncheckin method.
	 *
	 * @TODO use of 'order_id' in this method is misleading (we're working with the attendee id)
	 *       we should consider revising in a back-compat minded way
	 */
	public function ajax_handler_attendee_uncheckin() {

		if ( ! isset( $_POST['order_ID'] ) || intval( $_POST['order_ID'] ) == 0 ) {
			wp_send_json_error( 'Bad post' );
		}

		if ( ! isset( $_POST['provider'] ) || ! $this->module_is_valid( $_POST['provider'] ) ) {
			wp_send_json_error( 'Bad module' );
		}

		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'uncheckin' ) || ! $this->user_can( 'edit_posts', $_POST['order_ID'] ) ) {
			wp_send_json_error( "Cheatin' huh?" );
		}

		$order_id = $_POST['order_ID'];

		// Pass the control to the child object
		$did_uncheckin = $this->uncheckin( $order_id );

		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$this->maybe_update_attendees_cache( $did_uncheckin );
		}

		wp_send_json_success( $did_uncheckin );
	}

	/**
	 * Sanitizes the data for the delete ticket ajax call, and calls the child delete_ticket
	 * function.
	 */
	public function ajax_handler_ticket_delete() {

		if ( ! isset( $_POST['post_ID'] ) ) {
			wp_send_json_error( 'Bad post' );
		}

		if ( ! isset( $_POST['ticket_id'] ) ) {
			wp_send_json_error( 'Bad post' );
		}

		$post_id = $_POST['post_ID'];

		if ( ! $this->has_permission( $post_id, $_POST, 'remove_ticket_nonce' ) ) {
			wp_send_json_error( "Cheatin' huh?" );
		}

		$ticket_id = $_POST['ticket_id'];

		// Pass the control to the child object
		$return = $this->delete_ticket( $post_id, $ticket_id );

		// Successfully deleted?
		if ( $return ) {
			// Let's create a tickets list markup to return
			$tickets = $this->get_event_tickets( $post_id );
			$return  = tribe( 'tickets.handler' )->get_ticket_list_markup( $tickets );

			$return = $this->notice( esc_html__( 'Your ticket has been deleted.', 'event-tickets' ) ) . $return;

			/**
			 * Fire action when a ticket has been deleted
			 *
			 * @param int $post_id ID of parent "event" post
			 */
			do_action( 'tribe_tickets_ticket_deleted', $post_id );
		}

		wp_send_json_success( $return );
	}

	/**
	 * Returns the data from a single ticket to populate
	 * the edit form.
	 *
	 * @return array $return array of ticket data
	 */
	public function ajax_handler_ticket_edit() {

		if ( ! isset( $_POST['post_ID'] ) ) {
			wp_send_json_error( 'Bad post' );
		}

		if ( ! isset( $_POST['ticket_id'] ) ) {
			wp_send_json_error( 'Bad post' );
		}

		$post_id = $_POST['post_ID'];

		if ( ! $this->has_permission( $post_id, $_POST, 'edit_ticket_nonce' ) ) {
			wp_send_json_error( "Cheatin' huh?" );
		}

		$ticket_id = $_POST['ticket_id'];
		$ticket = $this->get_ticket( $post_id, $ticket_id );

		$return = get_object_vars( $ticket );
		$return['post_id'] = $post_id;
		/**
		 * Allow for the prevention of updating ticket price on update.
		 *
		 * @param boolean
		 * @param WP_Post
		 */
		$can_update_price = apply_filters( 'tribe_tickets_can_update_ticket_price', true, $ticket );

		$return['can_update_price'] = $can_update_price;

		if ( ! $can_update_price ) {
			/**
			 * Filter the no-update message that is displayed when updating the price is disallowed
			 *
			 * @param string
			 * @param WP_Post
			 */
			$return['disallow_update_price_message'] = apply_filters( 'tribe_tickets_disallow_update_ticket_price_message', esc_html__( 'Editing the ticket price is currently disallowed.', 'event-tickets' ), $ticket );
		}

		// Prevent HTML elements from being escaped
		$return['name']        = html_entity_decode( $return['name'], ENT_QUOTES );
		$return['name']        = htmlspecialchars_decode( $return['name'] );
		$return['description'] = html_entity_decode( $return['description'], ENT_QUOTES );
		$return['description'] = htmlspecialchars_decode( $return['description'] );

		ob_start();
		/**
		 * Fired to allow for the insertion of extra form data in the ticket int admin form
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param int $ticket_id ID of ticket post
		 */
		do_action( 'tribe_events_tickets_metabox_edit_advanced', $post_id, $ticket_id );

		$extra = ob_get_contents();
		ob_end_clean();

		$return['advanced_fields'] = $extra;
		$return['history'] = tribe( 'tickets.admin.views' )->template( 'tickets-history', array( 'post_id' => $post_id, 'ticket' => $ticket->ID ), false );

		/**
		 * Allows for the insertion of the attendee meta fields into the ticket admin form
		 *
		 * @since 4.6
		 *
		 * @param int $post_id ID of parent "event" post
		 * @param int $ticket_id ID of ticket post
		 */
		$return['attendee_fields']   = apply_filters( 'tribe_events_tickets_metabox_edit_attendee', $post_id, $ticket_id );

		$return['stock']             = $ticket->stock;
		$return['capacity']          = $ticket->capacity;
		$global_stock_mode           = ( isset( $ticket ) ) ? $ticket->global_stock_mode() : '';
		$return['global_stock_mode'] = $global_stock_mode;
		$return['show_description']  = $ticket->show_description();

		if ( Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE === $global_stock_mode || Tribe__Tickets__Global_Stock::CAPPED_STOCK_MODE === $global_stock_mode ) {
			$return['event_capacity'] = tribe_tickets_get_capacity( $post_id );
		}

		/**
		 * Provides an opportunity for final adjustments to the data used to populate
		 * the edit-ticket form.
		 *
		 * @param array $return data returned to the client
		 * @param Tribe__Events__Tickets $ticket_object
		 */
		$return = (array) apply_filters( 'tribe_events_tickets_ajax_ticket_edit', $return, $this );

		wp_send_json_success( $return );
	}

	/**
	 * test if the nonce is correct and the current user has the correct permissions
	 *
	 * @since  TBD
	 *
	 * @param  WP_Post  $post
	 * @param  array   $data
	 * @param  string  $nonce_action
	 *
	 * @return boolean
	 */
	public function has_permission( $post, $data, $nonce_action ) {
		if ( ! $post instanceof WP_Post ) {
			if ( ! is_numeric( $post ) ) {
				return false;
			}

			$post = get_post( $post );
		}

		return ! empty( $data['nonce'] ) && wp_verify_nonce( $data['nonce'], $nonce_action ) && current_user_can( get_post_type_object( $post->post_type )->cap->edit_posts );
	}

	/**
	 * Returns whether a class name is a valid active module/provider.
	 *
	 * @since  TBD
	 *
	 * @param  string  $module  class name of module
	 *
	 * @return bool
	 */
	public function module_is_valid( $module ) {
		return array_key_exists( $module, Tribe__Tickets__Tickets::modules() );
	}

	/**
	 * Returns the markup for a notice in the admin
	 *
	 * @since  TBD
	 *
	 * @param  string $msg Text for the notice
	 *
	 * @return string Notice with markup
	 */
	protected function notice( $msg ) {
		return sprintf( '<div class="wrap"><div class="updated"><p>%s</p></div></div>', $msg );
	}

	/**
	 * Decimal Character Asset Localization (used on Community Tickets)
	 *
	 * @todo   We need to deprecate this
	 *
	 * @return void
	 */
	public static function localize_decimal_character() {
		$locale  = localeconv();
		$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';

		/**
		 * Filter the decimal point character used in the price
		 */
		$decimal = apply_filters( 'tribe_event_ticket_decimal_point', $decimal );

		wp_localize_script( 'event-tickets-js', 'price_format', array(
			'decimal' => $decimal,
			'decimal_error' => __( 'Please enter in without thousand separators and currency symbols.', 'event-tickets' ),
		) );
	}


	/************************
	 *                      *
	 *  Deprecated Methods  *
	 *                      *
	 ************************/
	// @codingStandardsIgnoreStart

	/**
	 * Refreshes panel settings after canceling saving
	 *
	 * @deprecated TBD
	 * @since 4.6
	 *
	 * @return string html content of the panel settings
	 */
	public function ajax_refresh_settings() {

	}

	/**
	 * @deprecated TBD
	 *
	 * @return void
	 */
	public function ajax_handler_save_settings() {

	}

	/**
	 * Registers the tickets metabox if there's at least
	 * one Tribe Tickets module (provider) enabled
	 *
	 * @deprecated TBD
	 *
	 * @param $post_type
	 */
	public static function maybe_add_meta_box( $post_type ) {
		tribe( 'tickets.metabox' )->configure( $post_type );
	}

	/**
	 * Loads the content of the tickets metabox if there's at
	 * least one Tribe Tickets module (provider) enabled
	 *
	 * @deprecated TBD
	 *
	 * @param $post_id
	 */
	public static function do_modules_metaboxes( $post_id ) {
		tribe( 'tickets.metabox' )->render( $post_id );
	}

	/**
	 * Enqueue the tickets metabox JS and CSS
	 *
	 * @deprecated 4.6
	 *
	 * @param $unused_hook
	 */
	public static function add_admin_scripts( $unused_hook ) {
		_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets__Assets::admin_enqueue_scripts' );
	}

	// @codingStandardsIgnoreEnd
}
