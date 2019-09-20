<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class Tribe__Tickets__Tickets_View {

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return self
	 *
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Hook the necessary filters and Actions!
	 *
	 * @static
	 * @return self
	 */
	public static function hook() {
		$myself = self::instance();

		add_action( 'template_redirect', array( $myself, 'authorization_redirect' ) );
		add_action( 'template_redirect', array( $myself, 'update_tickets' ) );

		// Generate Non TEC Permalink
		add_action( 'generate_rewrite_rules', array( $myself, 'add_non_event_permalinks' ) );
		add_filter( 'query_vars', array( $myself, 'add_query_vars' ) );
		add_action( 'parse_request', array( $myself, 'prevent_page_redirect' ) );
		add_filter( 'the_content', array( $myself, 'intercept_content' ) );
		add_action( 'parse_request', array( $myself, 'maybe_regenerate_rewrite_rules' ) );

		// Only Applies this to TEC users
		if ( class_exists( 'Tribe__Events__Rewrite' ) ) {
			add_action( 'tribe_events_pre_rewrite', array( $myself, 'add_permalink' ) );
			add_filter( 'tribe_events_rewrite_base_slugs', array( $myself, 'add_rewrite_base_slug' ) );
		}

		// Intercept Template file for Tickets
		add_action( 'tribe_events_pre_get_posts', array( $myself, 'modify_ticket_display_query' ) );
		add_filter( 'tribe_events_template', array( $myself, 'intercept_template' ), 20, 2 );

		// We will inject on the Priority 4, to be happen before RSVP
		add_action( 'tribe_events_single_event_after_the_meta', array( $myself, 'inject_link_template' ), 4 );
		add_filter( 'the_content', array( $myself, 'inject_link_template_the_content' ), 9 );

		return $myself;
	}

	/**
	 * By default WordPress has a nasty if query_var['p'] is a page then redirect to the page
	 * so we will change the variables accordingly
	 *
	 * @param  WP_Query $query The current Query
	 * @return void
	 */
	public function prevent_page_redirect( $query ) {
		$is_correct_page = isset( $query->query_vars['tribe-edit-orders'] ) && $query->query_vars['tribe-edit-orders'];

		if ( ! $is_correct_page ) {
			return;
		}

		// This has no Performance problems, since get_post uses caching and we use this method later on.
		$post = isset( $query->query_vars['p'] ) ? get_post( absint( $query->query_vars['p'] ) ) : 0;
		if ( ! $post ) {
			return;
		}

		if ( ! tribe_tickets_post_type_enabled( $post->post_type ) ) {
			return;
		}

		$query->query_vars['post_type'] = $post->post_type;

		if ( 'page' === $post->post_type ) {
			// Unset the p variable, we dont need it anymore
			unset( $query->query_vars['p'] );

			// Set `page_id` for faster query
			$query->query_vars['page_id'] = $post->ID;
		}

	}

	/**
	 * Tries to Flush the Rewrite rules
	 *
	 * @return void
	 */
	public function maybe_regenerate_rewrite_rules() {
		// if they don't have any rewrite rules, do nothing
		// Don't try to run stuff for non-logged users (too time consuming)
		if ( ! is_array( $GLOBALS['wp_rewrite']->rules ) || ! is_user_logged_in() ) {
			return;
		}

		$rules = $this->rewrite_rules_array();

		$diff = array_diff( $rules, $GLOBALS['wp_rewrite']->rules );
		$key_diff = array_diff_assoc( $rules, $GLOBALS['wp_rewrite']->rules );

		if ( empty( $diff ) && empty( $key_diff ) ) {
			return;
		}

		flush_rewrite_rules();
	}

	/**
	 * Gets the List of Rewrite rules we are using here
	 *
	 * @return array
	 */
	public function rewrite_rules_array() {
		$bases = $this->add_rewrite_base_slug();

		$rules = array(
			sanitize_title_with_dashes( $bases['tickets'][0] ) . '/([0-9]{1,})/?' => 'index.php?p=$matches[1]&tribe-edit-orders=1',
		);

		return $rules;
	}

	/**
	 * For non events the links will be a little bit weird, but it's the safest way
	 *
	 * @param WP_Rewrite $wp_rewrite
	 */
	public function add_non_event_permalinks( WP_Rewrite $wp_rewrite  ) {
		$wp_rewrite->rules = $this->rewrite_rules_array() + $wp_rewrite->rules;
	}

	/**
	 * Register a new public (URL query parameters can use it) Query Var to allow tickets editing.
	 *
	 * @see \WP::parse_request()
	 *
	 * @param array $vars
	 *
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'tribe-edit-orders';
		return $vars;
	}


	/**
	 * Sort Attendee by Order Status to Process Not Going First
	 *
	 * @since 4.7.1
	 *
	 * @param $a array an array of ticket id and status
	 * @param $b array an array of ticket id and status
	 *
	 * @return int
	 */
	public function sort_attendees( $a, $b ) {
		return strcmp( $a['order_status'], $b['order_status'] );
	}

	/**
	 * Update the RSVP and Tickets values for each Attendee
	 */
	public function update_tickets() {
		$is_correct_page = $this->is_edit_page();

		// Now fetch the display and check it
		if (
			'tickets' !== get_query_var( 'eventDisplay', false )
			&& ! $is_correct_page
		) {
			return;
		}

		if (
			empty( $_POST['process-tickets'] )
			|| (
				empty( $_POST['attendee'] )
				&& empty( $_POST['tribe-tickets-meta'] )
			)
		) {
			return;
		}

		$post_id = get_the_ID();

		$attendees = ! empty( $_POST['attendee'] ) ? $_POST['attendee'] : array();

		/**
		 * Sort list to handle all not attending first
		 *
		 * @todo switch to only wp_list_sort once WordPress 4.7 is minimum supported version
		 */
		if ( function_exists( 'wp_list_sort' ) ) {
			$attendees = wp_list_sort( $attendees, 'order_status', 'ASC', true );
		} else {
			uasort( $attendees, array( $this, 'sort_attendees' ) );
		}

		foreach ( $attendees as $order_id => $data ) {
			/**
			 * An Action fired for each one of the Attendees that were posted on the Order Tickets page
			 *
			 * @var array $data     Information that we are trying to save.
			 * @var int   $order_id ID of attendee ticket.
			 * @var int   $post_id  ID of event.
			 */
			do_action( 'event_tickets_attendee_update', $data, $order_id, $post_id );
		}

		/**
		 * A way for Meta to be saved, because it's grouped in a different way
		 *
		 * @param int $post_id ID of event
		 */
		do_action( 'event_tickets_after_attendees_update', $post_id );

		// After editing the values, we update the transient.
		Tribe__Post_Transient::instance()->delete( $post_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );

		// If it's not events CPT
		$url = $this->get_tickets_page_url( $post_id, ! $is_correct_page );
		$url = add_query_arg( 'tribe_updated', 1, $url );
		wp_safe_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Helper function to generate the Link to the tickets page of an event
	 *
	 * @since 4.7.1
	 *
	 * @param $event_id
	 * @param $is_event_page
	 *
	 * @return string|void
	 */
	public function get_tickets_page_url( $event_id, $is_event_page ) {
		$has_plain_permalink = '' === get_option( 'permalink_structure' );
		$event_url = get_permalink( $event_id );

		// Is on the Event post type
		if ( $is_event_page ) {
			$link = $has_plain_permalink
				? add_query_arg( 'eventDisplay', 'tickets', untrailingslashit( $event_url ) )
				: trailingslashit( $event_url ) . 'tickets';
		} else {
			$link = $has_plain_permalink
				? add_query_arg( 'tribe-edit-orders', 1, untrailingslashit( $event_url ) )
				: home_url( '/tickets/' . $event_id );
		}

		return $link;
	}

	/**
	 * Makes sure only logged users can See the Tickets page.
	 *
	 * @return void
	 */
	public function authorization_redirect() {
		/**
		 * @todo Remove this after we implement the Rewrites in Common
		 */
		$is_event_query = ! empty( $GLOBALS['wp_query']->tribe_is_event_query );

		// When it's not Events Query and we have TEC active we dont care
		if ( class_exists( 'Tribe__Events__Main' ) && ! $is_event_query ) {
			return;
		}

		// If we got here and it's a 404 + single
		if ( is_single() && is_404() ) {
			return;
		}

		// Now fetch the display and check it
		if ( 'tickets' !== get_query_var( 'eventDisplay', false ) ) {
			return;
		}

		// Only goes to the Redirect if user is not logged in
		if ( is_user_logged_in() ) {
			return;
		}

		// Loop back to the Event, this page is only for Logged users
		wp_redirect( get_permalink() );
		exit;
	}

	/**
	 * To allow `tickets` to be translatable we need to add it as a base
	 *
	 * @param  array $bases The translatable bases
	 * @return array
	 */
	public function add_rewrite_base_slug( $bases = array() ) {
		/**
		 * Allows users to filter and change the base for the order page
		 *
		 * @param string $slug
		 * @param array  $bases
		 */
		$bases['tickets'] = (array) apply_filters( 'event_tickets_rewrite_slug_orders_page', 'tickets', $bases );

		return $bases;
	}


	/**
	 * Checks if this is the ticket page based on the current query var
	 *
	 * This only works after parse_query has run.
	 *
	 * @return bool
	 */
	public function is_edit_page() {
		return false !== get_query_var( 'tribe-edit-orders', false );
	}

	/**
	 * Adds the Permalink for the tickets end point
	 *
	 * @param Tribe__Events__Rewrite $rewrite
	 */
	public function add_permalink( Tribe__Events__Rewrite $rewrite ) {

		// Adds the 'tickets' endpoint for single event pages.
		$rewrite->single(
			array( '{{ tickets }}' ),
			array(
				Tribe__Events__Main::POSTTYPE => '%1',
				'post_type' => Tribe__Events__Main::POSTTYPE,
				'eventDisplay' => 'tickets',
			)
		);

		// Adds the `tickets` endpoint for recurring events
		$rewrite->single(
			array( '(\d{4}-\d{2}-\d{2})', '{{ tickets }}' ),
			array(
				Tribe__Events__Main::POSTTYPE => '%1',
				'eventDate' => '%2',
				'post_type' => Tribe__Events__Main::POSTTYPE,
				'eventDisplay' => 'tickets',
			)
		);

	}

	/**
	 * Intercepts the_content from the posts to include the orders structure
	 *
	 * @param  string $content Normally the_content of a post
	 * @return string
	 */
	public function intercept_content( $content = '' ) {
		// Prevents firing more then it needs too outside of the loop
		$in_the_loop = isset( $GLOBALS['wp_query']->in_the_loop ) && $GLOBALS['wp_query']->in_the_loop;

		// Prevents Weird
		if (
			! $this->is_edit_page()
			|| ! $in_the_loop
		) {
			return $content;
		}

		ob_start();

		include Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders.php' );

		$content = ob_get_clean();

		return $content;
	}


	/**
	 * Modify the front end ticket list display for it to always display
	 * even when Hide From Event Listings is checked for an event
	 *
	 * @since 4.7.3
	 *
	 * @param $query WP_Query Query object
	 *
	 */
	public function modify_ticket_display_query( $query ) {

		if ( ! $query->tribe_is_event_query ) {
			return;
		}

		if ( 'tickets' !== get_query_var( 'eventDisplay', false ) ) {
			return;
		}

		$query->set( 'post__not_in', '' );
	}

	/**
	 * We need to intercept the template loading and load the correct file
	 *
	 * @param  string $old_file Non important variable with the previous path
	 * @param  string $template Which template we are dealing with
	 * @return string           The correct File path for the tickets endpoint
	 */
	public function intercept_template( $old_file, $template ) {
		global $wp_query;

		/**
		 * @todo Remove this after we implement the Rewrites in Common
		 */
		$is_event_query = ! empty( $wp_query->tribe_is_event_query );

		// When it's not our query we don't care
		if ( ! $is_event_query ) {
			return $old_file;
		}

		// If we got here and it's a 404 + single
		if ( is_single() && is_404() ) {
			return $old_file;
		}

		// Now fetch the display and check it
		$display = get_query_var( 'eventDisplay', false );
		if ( 'tickets' !== $display ) {
			return $old_file;
		}

		// If for some reason it's not `single-event.php` we don't care either
		if ( 'single-event.php' !== $template ) {
			return $old_file;
		}

		// Fetch the correct file using the Tickets Hierarchy
		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders.php' );

		return $file;
	}

	/**
	 * Injects the Link to The front-end Tickets page normally at `tribe_events_single_event_after_the_meta`
	 *
	 * @return void
	 */
	public function inject_link_template() {
		/**
		 * A flag we can set via filter, e.g. at the end of this method, to ensure this template only shows once.
		 *
		 * @since 4.5.6
		 *
		 * @param boolean $already_rendered
		 */
		$already_rendered = apply_filters( 'tribe_tickets_order_link_template_already_rendered', false );

		if ( $already_rendered ) {
			return;
		}

		$event_id = get_the_ID();
		$user_id  = get_current_user_id();

		if ( ! $this->has_rsvp_attendees( $event_id, $user_id ) && ! $this->has_ticket_attendees( $event_id, $user_id ) ) {
			return;
		}

		if ( $this->is_edit_page() ) {
			return;
		}

		if ( ! tribe_tickets_post_type_enabled( get_post_type() ) ) {
			return;
		}

		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders-link.php' );

		/**
		 * @since 4.10.8 Attempt to load from old location to account for pre-existing theme overrides. If not found,
		 *            go through the motions with the new location.
		 */
		if ( empty( $file ) ) {
			$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/view-link.php' );
		}

		include $file;

		add_filter( 'tribe_tickets_order_link_template_already_rendered', '__return_true' );
	}

	/**
	 * Injects the Link to The front-end Tickets page to non Events
	 *
	 * @param string $content  The content form the post
	 * @return string $content
	 */
	public function inject_link_template_the_content( $content ) {
		// Prevents firing more then it needs too outside of the loop
		$in_the_loop = isset( $GLOBALS['wp_query']->in_the_loop ) && $GLOBALS['wp_query']->in_the_loop;

		$post_id = get_the_ID();
		$user_id = get_current_user_id();

		// if the current post type doesn't have tickets enabled for it, bail
		if ( ! tribe_tickets_post_type_enabled( get_post_type( $post_id ) ) ) {
			return $content;
		}

		/**
		 * @todo Remove this after we implement the Rewrites in Common
		 */
		$is_event_query = ! empty( $GLOBALS['wp_query']->tribe_is_event_query );

		// When it's not our query we don't care
		if ( ( class_exists( 'Tribe__Events__Main' ) && $is_event_query ) || ! $in_the_loop ) {
			return $content;
		}

		// If we have this we are already on the tickets page
		if ( $this->is_edit_page() ) {
			return $content;
		}

		if ( ! $this->has_rsvp_attendees( $post_id, $user_id ) && ! $this->has_ticket_attendees( $post_id, $user_id ) ) {
			return $content;
		}

		ob_start();

		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders-link.php' );

		/**
		 * @since 4.10.8 Attempt to load from old location to account for pre-existing theme overrides. If not found,
		 *            go through the motions with the new location.
		 */
		if ( empty( $file ) ) {
			$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/view-link.php' );
		}

		include $file;

		$content .= ob_get_clean();

		return $content;
	}

	/**
	 * Fetches from the Cached attendees list the ones that are relevant for this user and event
	 * Important to note that this method will bring the attendees organized by order id
	 *
	 * @param  int       $event_id      The Event ID it relates to
	 * @param  int|null  $user_id       An Optional User ID
	 * @param  boolean   $include_rsvp  If this should include RSVP, which by default is false
	 * @return array                    List of Attendees grouped by order id
	 */
	public function get_event_attendees_by_order( $event_id, $user_id = null, $include_rsvp = false ) {
		if ( ! $user_id ) {
			$attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );
		} else {
			// If we have a user_id then limit by that.
			$args = [
				'by' => [
					'user' => $user_id,
				],
			];

			$attendee_data = Tribe__Tickets__Tickets::get_event_attendees_by_args( $event_id, $args );

			$attendees = $attendee_data['attendees'];
		}

		$orders = array();

		foreach ( $attendees as $key => $attendee ) {
			// Ignore RSVP if we don't tell it specifically
			if ( 'rsvp' === $attendee['provider_slug'] && ! $include_rsvp ) {
				continue;
			}

			$orders[ (int) $attendee['order_id'] ][] = $attendee;
		}

		return $orders;
	}

	/**
	 * Fetches from the Cached attendees list the ones that are relevant for this user and event
	 * Important to note that this method will bring the attendees from RSVP
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return array                   Array with the RSVP attendees
	 */
	public function get_event_rsvp_attendees( $event_id, $user_id = null ) {
		$attendees = array();

		/** @var Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		if ( ! $user_id ) {
			return $rsvp->get_attendees_by_id( $event_id );
		}

		return $rsvp->get_attendees_by_user_id( $user_id, $event_id );
	}

	/**
	 * Groups RSVP attendees by purchaser name/email
	 *
	 * @param int $event_id The Event ID it relates to
	 * @param int|null $user_id An optional user ID
	 * @return array Array with the RSVP attendees grouped by purchaser name/email
	 */
	public function get_event_rsvp_attendees_by_purchaser( $event_id, $user_id = null ) {
		$attendees = $this->get_event_rsvp_attendees( $event_id, $user_id );

		if ( ! $attendees ) {
			return array();
		}

		$attendee_groups = array();
		foreach ( $attendees as $attendee ) {
			$key = $attendee['purchaser_name'] . '::' . $attendee['purchaser_email'];

			if ( ! isset( $attendee_groups[ $key ] ) ) {
				$attendee_groups[ $key ] = array();
			}

			$attendee_groups[ $key ][] = $attendee;
		}

		return $attendee_groups;
	}

	/**
	 * Gets a List of Possible RSVP answers
	 *
	 * @param string $selected    Allows users to check if an option exists or get it's label
	 * @param bool   $just_labels Whether just the options labels should be returned.
	 *
	 * @return array|bool An array containing the RSVP states, an array containing the selected
	 *                    option data or `false` if the selected option does not exist.
	 */
	public function get_rsvp_options( $selected = null, $just_labels = true ) {

		$options = tribe( 'tickets.status' )->get_status_options( 'rsvp' );

		/**
		 * Allow users to add more RSVP options.
		 *
		 * Additional RSVP options should be specified in the following formats:
		 *
		 *      [
		 *          'slug' => 'Option 1 label',
		 *          'slug' => [ 'label' => 'Option 3 label' ],
		 *          'slug' => [ 'label' => 'Option 2 label', 'decrease_stock_by' => 1 ],
		 *      ]
		 *
		 * The `decrease_stock_by` key can be omitted and will default to `1`.
		 *
		 * @param array $options
		 * @param string $selected
		 */
		$options = apply_filters( 'event_tickets_rsvp_options', $options, $selected );

		$options = array_filter( $options, array( $this, 'has_rsvp_format' ) );
		array_walk( $options, array( $this, 'normalize_rsvp_option' ) );

		// If an option was passed return it's label, but if doesn't exist return false
		if ( null !== $selected ) {
			return isset( $options[ $selected  ] ) ?
                $options[ $selected  ]['label'] : false;
		}

		return $just_labels ?
			array_combine( array_keys( $options ), wp_list_pluck( $options, 'label' ) )
			: $options;
	}

	/**
	 * Check if the RSVP is a valid option
	 *
	 * @param  string  $option Which rsvp option to check
	 * @return boolean
	 */
	public function is_valid_rsvp_option( $option ) {
		return in_array( $option, array_keys( $this->get_rsvp_options() ) );
	}

	/**
	 * Counts the amount of RSVP attendees.
	 *
	 * @param int      $event_id The Event ID it relates to.
	 * @param int|null $user_id  An Optional User ID.
	 *
	 * @return int
	 */
	public function count_rsvp_attendees( $event_id, $user_id = null ) {
		if ( ! $user_id && null !== $user_id ) {
			// No attendees for this user.
			return 0;
		}

		/** @var Tribe__Tickets__RSVP $rsvp */
		$rsvp = tribe( 'tickets.rsvp' );

		// Get total attendees count for all users.
		if ( ! $user_id ) {
			return $rsvp->get_attendees_count( $event_id );
		}

		// Get total attendees count for this user.
		return $rsvp->get_attendees_count_by_user( $event_id, $user_id );
	}

	/**
	 * Counts the Amount of Tickets attendees
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return int
	 */
	public function count_ticket_attendees( $event_id, $user_id = null ) {
		if ( ! $user_id && null !== $user_id ) {
			// No attendees for this user.
			return 0;
		}

		$args = [
			'by' => [
				'provider__not_in' => 'rsvp',
			],
		];

		// Get total attendees count for this user.
		if ( $user_id ) {
			$args['by']['user'] = $user_id;
		}

		return Tribe__Tickets__Tickets::get_event_attendees_count( $event_id, $args );
	}

	/**
	 * Verifies if we have RSVP attendees for this user and event
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return int
	 */
	public function has_rsvp_attendees( $event_id, $user_id = null ) {
		$rsvp_orders = $this->count_rsvp_attendees( $event_id, $user_id );
		return ! empty( $rsvp_orders );
	}

	/**
	 * Verifies if we have Tickets attendees for this user and event
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return int
	 */
	public function has_ticket_attendees( $event_id, $user_id = null ) {
		$ticket_orders = $this->count_ticket_attendees( $event_id, $user_id );
		return ! empty( $ticket_orders );
	}

	/**
	 * Gets the name(s) of the type(s) of ticket(s) the specified user (optional) has for the specified event.
	 *
	 * @since 4.2
	 * @since 4.10.8 Deprecated the 3rd parameter (whether or not to use 'plurals') in favor of figuring it out per type.
	 *
	 * @param int      $event_id   The Event ID it relates to.
	 * @param int|null $user_id    An optional User ID.
	 * @param null     $deprecated Deprecated argument.
	 *
	 * @return string
	 */
	public function get_description_rsvp_ticket( $event_id, $user_id = null, $deprecated = null ) {
		$descriptions = [];

		$rsvp_count = $this->count_rsvp_attendees( $event_id, $user_id );

		$ticket_count = $this->count_ticket_attendees( $event_id, $user_id );

		if ( 1 === $rsvp_count ) {
			$descriptions[] = tribe_get_rsvp_label_singular( 'tickets_view_description' );
		} elseif ( 1 < $rsvp_count ) {
			$descriptions[] = tribe_get_rsvp_label_plural( 'tickets_view_description' );
		}

		if ( 1 === $ticket_count ) {
			$descriptions[] = tribe_get_ticket_label_singular( 'tickets_view_description' );
		} elseif ( 1 < $ticket_count ) {
			$descriptions[] = tribe_get_ticket_label_plural( 'tickets_view_description' );
		}

		// Just return false if array is empty
		if ( empty( $descriptions ) ) {
			return '';
		}

		return implode( esc_html_x( ' and ', 'separator if there are both RSVPs and Tickets', 'event-tickets' ), $descriptions );
	}

	/**
	 * Creates the HTML for the Select Element for RSVP options
	 *
	 * @param  string $name     The Name of the Field
	 * @param  string $selected The Current selected option
	 * @param  int  $event_id   The Event/Post ID (optional)
	 * @param  int  $ticket_id  The Ticket/RSVP ID (optional)
	 * @return void
	 */
	public function render_rsvp_selector( $name, $selected, $event_id = null, $ticket_id = null ) {
		$options = $this->get_rsvp_options();

		?>
		<select <?php echo $this->get_restriction_attr( $event_id, $ticket_id ); ?> name="<?php echo esc_attr( $name ); ?>">
		<?php foreach ( $options as $value => $label ): ?>
			<option <?php selected( $selected, $value ); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Verifies if the Given Event has RSVP restricted
	 *
	 * @param  int  $event_id   The Event/Post ID (optional)
	 * @param  int  $ticket_id  The Ticket/RSVP ID (optional)
	 * @param  int  $user_id    An User ID (optional)
	 * @return boolean
	 */
	public function is_rsvp_restricted( $event_id = null, $ticket_id = null, $user_id = null ) {
		// By default we always pass the current User
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		/**
		 * Allow users to filter if this Event or Ticket has Restricted RSVP
		 *
		 * @param  boolean  $restricted Is this Event or Ticket Restricted?
		 * @param  int      $event_id   The Event/Post ID (optional)
		 * @param  int      $ticket_id  The Ticket/RSVP ID (optional)
		 * @param  int      $user_id    An User ID (optional)
		 */
		return apply_filters( 'event_tickets_is_rsvp_restricted', false, $event_id, $ticket_id, $user_id );
	}

	/**
	 * Gets a HTML Attribute for input/select/textarea to be disabled.
	 *
	 * @param int $event_id  The Event/Post ID (optional).
	 * @param int $ticket_id The Ticket/RSVP ID (optional).
	 *
	 * @return bool
	 */
	public function get_restriction_attr( $event_id = null, $ticket_id = null ) {
		$is_disabled = '';

		if ( $this->is_rsvp_restricted( $event_id, $ticket_id ) ) {
			$is_disabled = 'disabled title="' . esc_attr( sprintf( __( 'This %s is no longer active.', 'event-tickets' ), tribe_get_rsvp_label_singular( 'rsvp_restricted_title_text' ) ) ) . '"';
		}

		return $is_disabled;
	}

	/**
	 * Creates the HTML for the status of the  RSVP choice.
	 *
	 * @param  string $name     The Name of the Field
	 * @param  string $selected The Current selected option
	 * @param  int  $event_id   The Event/Post ID (optional)
	 * @param  int  $ticket_id  The Ticket/RSVP ID (optional)
	 * @return void
	 */
	public function render_rsvp_status( $name, $selected, $event_id = null, $ticket_id = null ) {
		$options = $this->get_rsvp_options();
		echo sprintf( '<span>%s</span>', esc_html( $options[ $selected ] ) );
	}

	/**
	 * Prunes RSVP options that are arrays and are not defining a label.
	 *
	 * @param array|string $option
	 *
	 * @return bool
	 */
	protected function has_rsvp_format( $option ) {
		if ( ! is_array( $option ) ) {
			return true;
		}

		// label is the bare minimum
		if ( ! isset( $option['label'] ) ) {
			return false;
		}

		return empty( $option['decrease_stock_by'] )
		       || (
					is_numeric( $option['decrease_stock_by'] )
		            && intval( $option['decrease_stock_by'] ) == $option['decrease_stock_by']
		            && intval( $option['decrease_stock_by'] ) >= 0
		       );
	}

	/**
	 * Normalizes the RSVP option conforming it to the array format.
	 *
	 * @param array|string $option
	 */
	protected function normalize_rsvp_option( &$option ) {
		$label_only_format = ! is_array( $option );
		if ( $label_only_format ) {
			$option = array( 'label' => $option, 'decrease_stock_by' => 1 );
		} else {
			$option['decrease_stock_by'] = isset( $option['decrease_stock_by'] ) ? $option['decrease_stock_by'] : 1;
		}
	}
}
