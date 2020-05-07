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
	protected $params = array();

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

		add_shortcode( $this->shortcode_name, array( $this, 'generate' ) );
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
		$this->params = shortcode_atts( [
			'limit' => - 1,
			'user'  => get_current_user_id(),
		], $params, $this->shortcode_name );
	}

	/**
	 * Gets the user's attendance data and passes it to the relevant view.
	 */
	protected function generate_attendance_list() {
		$event_ids = $this->get_upcoming_attendances();

		include Tribe__Tickets__Templates::get_template_hierarchy( 'shortcodes/my-attendance-list' );
	}

	/**
	 * Queries for events which have attendee posts related to whichever user
	 * we are interested in.
	 *
	 * @return array
	 */
	protected function get_upcoming_attendances() {
		$events_orm = tribe_events();

		// Limit to a specific number of events.
		if ( 0 < $this->params['limit'] ) {
			$events_orm->per_page( $this->params['limit'] );
		}

		// Order by event date.
		$events_orm->order_by( 'event_date' );

		// Events that have not yet ended.
		$events_orm->by( 'ends_on_or_before', current_time( 'mysql' ) );

		// Events with attendees by the specific user ID.
		$events_orm->by( 'attendee_user', $this->params['user'] );

		return $events_orm->get_ids();
	}
}
