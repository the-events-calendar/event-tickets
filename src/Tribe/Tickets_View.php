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
		$main = Tribe__Tickets__Main::instance();

		add_action( 'template_redirect', array( $myself, 'authorization_redirect' ) );
		add_action( 'template_redirect', array( $myself, 'update_tickets' ) );

		// Generate Non TEC Permalink
		add_action( 'generate_rewrite_rules', array( $myself, 'add_non_event_permalinks' ) );
		add_filter( 'query_vars', array( $myself, 'add_query_vars' ) );
		add_filter( 'the_content', array( $myself, 'intercept_content' ) );

		// Only Applies this to TEC users
		if ( class_exists( 'Tribe__Events__Rewrite' ) ) {
			add_action( 'tribe_events_pre_rewrite', array( $myself, 'add_permalink' ) );
			add_filter( 'tribe_events_rewrite_base_slugs', array( $myself, 'add_rewrite_base_slug' ) );
		}

		// Intercept Template file for Tickets
		add_action( 'tribe_events_template', array( $myself, 'intercept_template' ), 20, 2 );

		// We will inject on the Priority 4, to be happen before RSVP
		add_action( 'tribe_events_single_event_after_the_meta', array( $myself, 'inject_link_template' ), 4 );
		add_filter( 'the_content', array( $myself, 'inject_link_template_the_content' ) );

		return $myself;
	}


	/**
	 * For non events the links will be a little bit weird, but it's the safest way
	 *
	 * @param WP_Rewrite $wp_rewrite
	 */
	public function add_non_event_permalinks( WP_Rewrite $wp_rewrite  ) {
		$rules = array(
			'tickets/([0-9]{1,})/?' => 'index.php?p=$matches[1]&tribe-edit-orders=1',
		);

		$wp_rewrite->rules = $rules + $wp_rewrite->rules;
	}

	/**
	 * Add a new Query Var to allow tickets editing
	 * @param array $vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'tribe-edit-orders';
		return $vars;
	}

	/**
	 * Update the RSVP and Tickets values for each Attendee
	 */
	public function update_tickets() {
		// Now fetch the display and check it
		$display = get_query_var( 'eventDisplay', false );
		if ( 'tickets' !== $display ) {
			return;
		}

		if ( empty( $_POST['process-tickets'] ) || empty( $_POST['attendee'] ) ) {
			return;
		}

		$event_id = get_the_ID();
		$attendees = $_POST['attendee'];

		foreach ( $attendees as $order_id => $data ) {
			/**
			 * An Action fired after each Ticket/RSVP is Updated
			 *
			 * @var $order_id ID of attendee ticket
			 * @var $event_id ID of event
			 */
			do_action( 'event_tickets_attendee_updated', $data, $order_id, $event_id );
		}

		/**
		 * A way for Meta to be saved, because it's groupped in a diferent way
		 *
		 * @var $event_id ID of event
		 */
		do_action( 'event_tickets_after_attendees_update', $event_id );

		// After Editing the Values we Update the Transient
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );

		$url = get_permalink( $event_id ) . '/tickets';
		$url = add_query_arg( 'tribe_updated', 1, $url );
		wp_redirect( esc_url_raw( $url ) );
		exit;
	}


	/**
	 * Makes sure only logged users can See the Tickets page.
	 *
	 * @return void
	 */
	public function authorization_redirect() {
		// When it's not our query we don't care
		if ( ! tribe_is_event_query() ) {
			return;
		}

		// If we got here and it's a 404 + single
		if ( is_single() && is_404() ) {
			return;
		}

		// Now fetch the display and check it
		$display = get_query_var( 'eventDisplay', false );
		if ( 'tickets' !== $display ) {
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
	public function add_rewrite_base_slug( $bases ) {
		$bases['tickets'] = apply_filters( 'tribe_tickets_single_public_tickets_rewrite_base', array( 'tickets' ) );

		return $bases;
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
				'eventDisplay' => 'tickets'
			)
		);

	}

	public function intercept_content( $content ) {
		$is_correct_page = get_query_var( 'tribe-edit-orders', false );
		if ( ! $is_correct_page ) {
			return $content;
		}

		ob_start();
		include Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders.php' );
		$content = ob_get_clean();

		return $content;
	}

	/**
	 * We need to intercept the template loading and load the correct file
	 *
	 * @param  string $old_file Non important variable with the previous path
	 * @param  string $template Which template we are dealing with
	 * @return string           The correct File path for the tickets endpoint
	 */
	public function intercept_template( $old_file, $template ) {
		// When it's not our query we don't care
		if ( ! tribe_is_event_query() ) {
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

		// Fetch the Correct File using the Tickets Hiearchy
		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders.php' );

		return $file;
	}

	/**
	 * Injects the Link to The front-end Tickets page normally at `tribe_events_single_event_after_the_meta`
	 *
	 * @return void
	 */
	public function inject_link_template() {
		$event_id = get_the_ID();
		$user_id = get_current_user_id();

		if ( ! $this->has_rsvp_attendees( $event_id, $user_id ) && ! $this->has_rsvp_attendees( $event_id, $user_id ) ) {
			return;
		}

		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders-link.php' );

		include $file;
	}

	/**
	 * Injects the Link to The front-end Tickets page to non Events
	 *
	 * @param string $content  The content form the post
	 * @return string $content
	 */
	public function inject_link_template_the_content( $content ) {
		$event_id = get_the_ID();
		$user_id = get_current_user_id();

		// If we have this we are already on the tickets page
		$is_correct_page = get_query_var( 'tribe-edit-orders', false );
		if ( $is_correct_page ) {
			return $content;
		}

		if ( ! $this->has_rsvp_attendees( $event_id, $user_id ) && ! $this->has_rsvp_attendees( $event_id, $user_id ) ) {
			return $content;
		}

		ob_start();
		include Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/orders-link.php' );
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
		$attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );
		$orders = array();

		foreach ( $attendees as $key => $attendee ) {
			// Ignore RSVP if we don't tell it specifically
			if ( 'rsvp' === $attendee['provider_slug'] && ! $include_rsvp ) {
				continue;
			}

			// If we have a user_id then test it and ignore the ones that don't have it
			if ( ! is_null( $user_id ) ) {
				if ( empty( $attendee['user_id'] ) || $attendee['user_id'] != $user_id ) {
					continue;
				}
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
		$all_attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );
		$attendees = array();

		foreach ( $all_attendees as $key => $attendee ) {
			// Skip Non RSVP
			if ( 'rsvp' !== $attendee['provider_slug'] ) {
				continue;
			}

			// If we have a user_id then test it and ignore the ones that don't have it
			if ( ! is_null( $user_id ) ) {
				if ( empty( $attendee['user_id'] ) || $attendee['user_id'] != $user_id ) {
					continue;
				}
			}

			$attendees[] = $attendee;
		}

		return $attendees;
	}

	/**
	 * Gets a List of Possible RSVP answers
	 * @return array
	 */
	public function get_rsvp_options() {
		$options = array(
			'yes' => __( 'Going', 'event-tickets' ),
			'no' => __( 'Not Going', 'event-tickets' ),
		);

		/**
		 * Allow users to add more RSVP options
		 * @param array $options
		 */
		return apply_filters( 'tribe_tickets_rsvp_options', $options );
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
	 * Counts the Amount of RSVP attendees
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return int
	 */
	public function count_rsvp_attendees( $event_id, $user_id = null ) {
		$rsvp_orders = Tribe__Tickets__Tickets_View::get_event_rsvp_attendees( $event_id, $user_id );
		return count( $rsvp_orders );
	}

	/**
	 * Counts the Amount of Tickets attendees
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return int
	 */
	public function count_ticket_attendees( $event_id, $user_id = null ) {
		$ticket_orders = Tribe__Tickets__Tickets_View::get_event_attendees_by_order( $event_id, $user_id );
		$i = 0;
		foreach ( $ticket_orders as $orders ) {
			foreach ( $orders as $attendee ) {
				$i++;
			}
		}
		return $i;
	}

	/**
	 * Verifies if we have RSVP attendees for this user and event
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @return int
	 */
	public function has_rsvp_attendees( $event_id, $user_id = null ) {
		$rsvp_orders = Tribe__Tickets__Tickets_View::get_event_rsvp_attendees( $event_id, $user_id );
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
		$ticket_orders = Tribe__Tickets__Tickets_View::get_event_attendees_by_order( $event_id, $user_id );
		return ! empty( $ticket_orders );
	}

	/**
	 * Gets a String to descript which type of Tickets/RSVP we are dealign with
	 *
	 * @param  int       $event_id     The Event ID it relates to
	 * @param  int|null  $user_id      An Optional User ID
	 * @param  boolean   $plurals      Return the Strings as Plural
	 * @return int
	 */
	public function get_description_rsvp_ticket( $event_id, $user_id = null, $plurals = false ) {
		$what_to_update = array();

		if ( $this->has_rsvp_attendees( $event_id, $user_id ) ) {
			$what_to_update[] = $plurals ? esc_html__( 'RSVPs', 'event-tickets' ) : esc_html__( 'RSVP', 'event-tickets' );
		}

		if ( $this->has_ticket_attendees( $event_id, $user_id ) ) {
			$what_to_update[] = $plurals ? esc_html__( 'Tickets', 'event-tickets' ) : esc_html__( 'Ticket', 'event-tickets' );
		}

		// Just Return false if array is empty
		if ( empty( $what_to_update ) ) {
			return false;
		}

		return implode( esc_html__( ' and ', 'event-tickets' ), $what_to_update );
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
		 * @param  boolean  $restricted Is this Event or Ticket Restricted?
		 * @param  int      $event_id   The Event/Post ID (optional)
		 * @param  int      $ticket_id  The Ticket/RSVP ID (optional)
		 * @param  int      $user_id    An User ID (optional)
		 */
		return apply_filters( 'tribe_tickets_rsvp_restriction', false, $event_id, $ticket_id, $user_id );
	}

	/**
	 * Gets a HTML Attribute for input/select/textarea to be disabled
	 *
	 * @param  int  $event_id   The Event/Post ID (optional)
	 * @param  int  $ticket_id  The Ticket/RSVP ID (optional)
	 * @return boolean
	 */
	public function get_restriction_attr( $event_id = null, $ticket_id = null ) {
		$is_disabled = '';
		if ( $this->is_rsvp_restricted( $event_id, $ticket_id ) ) {
			$is_disabled = 'disabled title="' . esc_attr__( 'This RSVP is no longer active.', 'event-tickets' ) . '"';
		}

		return $is_disabled;
	}

}
