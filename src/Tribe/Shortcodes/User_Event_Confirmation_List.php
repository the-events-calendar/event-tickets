<?php
/**
 * Provides a shortcode which generates a list of events that the current user
 * has indicated they will attend. Works for all ticketing providers.
 *
 * Simple example (will assume the current user as the person of interest):
 *
 *     [tribe-user-event-confirmations]
 *
 * Example specifying a user:
 *
 *     [tribe-user-event-confirmations user="512"]
 *
 * Example specifying a limit to the number of events which should be returned:
 *
 *     [tribe-user-event-confirmations limit="16"]
 */
class Tribe__Tickets__Shortcodes__User_Event_Confirmation_List {
	protected $shortcode_name = 'tribe-user-event-confirmations';
	protected $params = [];

	/**
	 * Registers a user event confirmation list shortcode
	 *
	 * @since 4.5.2 moved the $shortcode_name parameter to a protected property
	 *        as it's needs to be used in other methods
	 */
	public function __construct( ) {
		/**
		 * Provides an opportunity to modify the registered shortcode name
		 * for the frontend attendee list.
		 *
		 * @param string $shortcode_name
		 */
		$this->shortcode_name = apply_filters( 'tribe_tickets_shortcodes_attendee_list_name', $this->shortcode_name );

		add_shortcode( $this->shortcode_name, [ $this, 'generate' ] );
	}

	/**
	 * Generate the user event confirmation list.
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public function generate( $params ) {
		$this->set_params( $params );

		ob_start();

		if ( ! is_user_logged_in() ) {
			include Tribe__Tickets__Templates::get_template_hierarchy( 'shortcodes/my-attendance-list-logged-out' );
		} else {
			$this->generate_attendance_list();
		}

		return ob_get_clean();
	}

	/**
	 * Given a set of parameters, ensure that the expected keys are present
	 * and set to reasonable defaults where necessary.
	 *
	 * @param $params
	 */
	protected function set_params( $params ) {
		/**
		 * Allow filtering of the default limit for the [tribe-user-event-confirmations] shortcode.
		 * @since TBD
		 *
		 * @param int $default_limit The default limit to use.
		 */
		$default_limit = apply_filters( 'tribe_tickets_shortcodes_attendee_list_limit', 100 );

		$this->params = shortcode_atts( [
			'limit' => -1,
			'user'  => get_current_user_id()
		], $params, $this->shortcode_name );

		$this->params['limit'] = (int) $this->params['limit'];
		$this->params['user']  = absint( $this->params['user'] );
	}

	/**
	 * Gets the user's attendance data and passes it to the relevant view.
	 */
	protected function generate_attendance_list() {
		$event_ids = $this->get_upcoming_attendances();
		include Tribe__Tickets__Templates::get_template_hierarchy( 'shortcodes/my-attendance-list' );
	}

	/**
	 * Get list of upcoming event IDs for which the specified user is an attendee.
	 *
	 * If attending Tribe Events event, only displays upcoming (not yet ended).
	 * If attending another type of post (e.g. Post or Page), only displays ones where ticket sales are not yet ended.
	 *
	 * @return array
	 */
	protected function get_upcoming_attendances() {
		if ( empty( $this->params['user'] ) ) {
			return [];
		}

		$post_ids                    = [];
		$ticket_ids                  = [];
		$event_ids                   = [];
		$upcoming_non_event_post_ids = [];

		// Get all of this user's attendee records

		/** @var Tribe__Tickets__Attendee_Repository $attendees */
		$attendees = tribe_attendees();

		$attendee_ids = $attendees
			->by( 'user', $this->params['user'] )
			->fields( 'ids' )
			->get_ids();

		// Build list of Posts and Tickets this user's attendance applies to

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		foreach ( $attendee_ids as $attendee_id ) {
			$connection = $tickets_handler->get_object_connections( $attendee_id );

			if ( ! empty( $connection->event ) ) {
				$post_ids[] = $connection->event;
			}

			if ( ! empty( $connection->product ) ) {
				$ticket_ids[] = $connection->product;
			}
		}

		$post_ids = array_unique( $post_ids );

		// Restrict to upcoming Tribe Events posts
		if ( function_exists( 'tribe_events' ) ) {
			/** @var Tribe__Events__Repositories__Event $events */
			$events = tribe_events();

			$event_ids = $events
				->by( 'ends_after', 'now' )
				->in( $post_ids )
				->fields( 'ids' )
				->get_ids();
		}

		// Restrict non-Tribe Events posts to those for which ticket availability end date has not ended (regardless of available capacity)
		$ticket_ids = array_unique( $ticket_ids );

		/** @var Tribe__Tickets__Ticket_Repository $tickets */
		$tickets = tribe_tickets();

		$ticket_ids_upcoming = $tickets
			->in( $ticket_ids )
			->by( 'available_until', 'now' )
			->fields( 'ids' )
			->get_ids();

		foreach ( $ticket_ids_upcoming as $ticket_id_upcoming ) {
			$connection = $tickets_handler->get_object_connections( $ticket_id_upcoming );

			if ( ! empty( $connection->event ) ) {
				$upcoming_non_event_post_ids[] = $connection->event;
			}
		}

		// Get list of Tribe Events posts plus Non-Tribe Events posts
		$result = array_merge( $event_ids, $upcoming_non_event_post_ids );

		return $result;
	}
}
