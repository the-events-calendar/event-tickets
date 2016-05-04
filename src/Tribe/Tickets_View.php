<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

class Tribe__Tickets__Tickets_View {

	/**
	 * Key for the Restrictions on the RSVP
	 */
	const RSVP_RESTRICTIONS_KEY = '_tribe_events_rsvp_restrictions';

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
		add_action( 'tribe_events_pre_rewrite', array( $myself, 'add_permalink' ) );
		add_filter( 'tribe_events_rewrite_base_slugs', array( $myself, 'add_rewrite_base_slug' ) );

		// Intercept Template file for Tickets
		add_action( 'tribe_events_template', array( $myself, 'intercept_template' ), 20, 2 );

		// We will inject on the Priority 4, to be happen before RSVP
		add_action( 'tribe_events_single_event_after_the_meta', array( $myself, 'inject_link_template' ), 4 );

		// Saves all Restrictions for the Event
		foreach ( $main->post_types() as $post_type ) {
			add_action( 'save_post_' . $post_type, array( $myself, 'save_rsvp_restriction' ) );
		}

		return $myself;
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
		if ( is_user_logged_in() ){
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
		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/user-tickets.php' );

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

		$file = Tribe__Tickets__Templates::get_template_hierarchy( 'tickets/link-tickets.php' );

		include $file;
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
	 * @param  int    $event_id Related Event (optional)
	 * @return void
	 */
	public function render_rsvp_selector( $name, $selected, $event_id = null ) {
		$options = $this->get_rsvp_options();

		$is_disabled = false;
		if ( $this->is_event_rsvp_restricted( $event_id ) ) {
			$is_disabled = 'disabled title="' . esc_attr__( 'This RSVP is no longer active.', 'event-tickets' ) . '"';
		}

		?>
		<select <?php echo $is_disabled; ?> name="<?php echo esc_attr( $name ); ?>">
		<?php foreach ( $options as $value => $label ): ?>
			<option <?php selected( $selected, $value ); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Saves the RSVP Restrictions
	 *
	 * @param int $post_id
	 */
	public function save_rsvp_restriction( $post_id ) {
		if ( ! ( isset( $_POST[ 'tribe-tickets-post-settings' ] ) && wp_verify_nonce( $_POST[ 'tribe-tickets-post-settings' ], 'tribe-tickets-meta-box' ) ) ) {
			return;
		}

		// Bail on autosaves/bulk updates
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$rsvp_restrictions = $_POST['tribe-tickets-restrict-rsvp-changes'];

		$options = $this->get_restrictions();
		if ( ! in_array( $rsvp_restrictions, array_keys( $options ) ) ) {
			return;
		}

		update_post_meta( $post_id, self::RSVP_RESTRICTIONS_KEY, $rsvp_restrictions );
	}

	/**
	 * Verifies if the Given Event has RSVP restricted
	 *
	 * @param  int  $event_id  The Event to Check
	 * @return boolean
	 */
	public function is_event_rsvp_restricted( $event_id ) {
		$restriction = get_post_meta( $event_id, self::RSVP_RESTRICTIONS_KEY, true );

		return false;
	}

	/**
	 * Gets a list of the possible Restrictions
	 *
	 * @return array
	 */
	public function get_restrictions() {
		$options = array(
			'no-restriction' => __( 'No Restrictions', 'event-tickets' ),
			'before-of' => __( 'Before the Event', 'event-tickets' ),
			'day-of' => __( 'On the day of the Event', 'event-tickets' ),
			'week-before' => __( 'A Week Before', 'event-tickets' ),
			'month-before' => __( 'A Month Before', 'event-tickets' ),
		);

		/**
		 * Allows third-parties to filter and add more restrictions
		 * If you add a new Restriction you need to also filter on the method `is_event_rsvp_restricted`
		 */
		return apply_filters( 'tribe_tickets_rsvp_restrictions', $options );
	}

	/**
	 * Creates the HTML for the Select Element for Restrictions options
	 *
	 * @param  string $name     The Name of the Field
	 * @param  string $selected The Current selected option
	 *
	 * @return void
	 */
	public function render_restrictions_selector( $name, $selected ) {
		$options = $this->get_restrictions();
	?>
		<select name="<?php echo esc_attr( $name ); ?>" id="<?php echo esc_attr( $name ); ?>">
		<?php foreach ( $options as $value => $label ): ?>
			<option <?php selected( $selected, $value ); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
		</select>
	<?php
	}
}
