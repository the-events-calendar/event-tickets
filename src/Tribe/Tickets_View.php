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

	public function get_event_attendees_by_order( $event_id, $include_rsvp = false ) {
		$attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );
		$orders = array();

		foreach ( $attendees as $key => $attendee ) {
			if ( 'rsvp' === $attendee['provider_slug'] && ! $include_rsvp ) {
				continue;
			}

			$orders[ (int) $attendee['order_id'] ][] = $attendee;
		}

		return $orders;
	}

	public function get_event_rsvp_attendees( $event_id ) {
		$all_attendees = Tribe__Tickets__Tickets::get_event_attendees( $event_id );
		$attendees = array();

		foreach ( $all_attendees as $key => $attendee ) {
			if ( 'rsvp' !== $attendee['provider_slug'] ) {
				continue;
			}

			$attendees[] = $attendee;
		}

		return $attendees;
	}
}
