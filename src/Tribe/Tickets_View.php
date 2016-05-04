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
		add_action( 'tribe_events_pre_rewrite', array( $myself, 'add_permalink' ) );
		add_filter( 'tribe_events_rewrite_base_slugs', array( $myself, 'add_rewrite_base_slug' ) );

		// Intercept Template file for Tickets
		add_action( 'tribe_events_template', array( $myself, 'intercept_template' ), 20, 2 );

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

	public function get_rsvp_options() {
		$options = array(
			'yes' => __( 'Going', 'event-tickets' ),
			'no' => __( 'Not Going', 'event-tickets' ),
		);

		return apply_filters( 'tribe_tickets_rsvp_options', $options );
	}

	public function is_valid_rsvp_option( $option ) {
		return in_array( $option, array_keys( $this->get_rsvp_options() ) );
	}

	public function has_rsvp_attendees( $event_id, $user_id = null ) {
		$rsvp_orders = Tribe__Tickets__Tickets_View::get_event_rsvp_attendees( $event_id, $user_id );
		return ! empty( $rsvp_orders );
	}

	public function has_ticket_attendees( $event_id, $user_id = null ) {
		$ticket_orders = Tribe__Tickets__Tickets_View::get_event_attendees_by_order( $event_id, $user_id );
		return ! empty( $ticket_orders );
	}

	public function get_description_rsvp_ticket( $event_id, $user_id = null ) {
		$what_to_update = array();

		if ( $this->has_rsvp_attendees( $event_id, $user_id ) ) {
			$what_to_update[] = esc_html__( 'RSVP', 'event-tickets' );
		}

		if ( $this->has_ticket_attendees( $event_id, $user_id ) ) {
			$what_to_update[] = esc_html__( 'Tickets', 'event-tickets' );
		}

		// Just Return false if array is empty
		if ( empty( $what_to_update ) ) {
			return false;
		}

		return implode( esc_html__( ' and ', 'event-tickets' ), $what_to_update );
	}

	public function render_rsvp_selector( $name, $selected ) {
		$options = $this->get_rsvp_options();
		?>
		<select name="<?php echo esc_attr( $name ); ?>">
		<?php foreach ( $options as $value => $label ): ?>
			<option <?php selected( $selected, $value ); ?> value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
		</select>
		<?php
	}
}
