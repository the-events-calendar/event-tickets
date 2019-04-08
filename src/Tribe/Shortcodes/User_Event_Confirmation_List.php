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
		$this->params = shortcode_atts( array(
			'limit' => -1,
			'user'  => get_current_user_id()
		), $params, $this->shortcode_name );
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
		global $wpdb;

		// Use a limit if set to a positive value
		$limit = $this->params['limit'] > 0
			? 'LIMIT ' . $wpdb->prepare( '%d', $this->params['limit'] )
			: '';

		$keys = $this->build_escaped_key_list( $this->get_event_keys() );

		$query = "
			SELECT ID
			FROM {$wpdb->postmeta} AS match_user

			JOIN {$wpdb->postmeta} AS match_events
			  ON match_events.post_id = match_user.post_id

			JOIN {$wpdb->posts} AS event_list
			  ON match_events.meta_value = ID

			JOIN {$wpdb->postmeta} AS event_end_dates
			  ON event_end_dates.post_id = ID

			WHERE (
				-- Match the user
				match_user.meta_key = '_tribe_tickets_attendee_user_id'
				AND match_user.meta_value = %d
			) AND (
				-- Restrict to upcoming events
				match_events.meta_key IN ( $keys )
				AND event_end_dates.meta_key = '_EventEndDateUTC'
				AND event_end_dates.meta_value > %s
			)
			AND NOT EXISTS (
				SELECT 1
				  FROM wp_postmeta ticket_status
				 WHERE ticket_status.meta_key = '_wp_trash_meta_status'
				   AND ticket_status.post_id = match_events.post_id
			)
			GROUP BY ID, event_end_dates.meta_value
			ORDER BY event_end_dates.meta_value

			$limit
		";

		return (array) $wpdb->get_col( $wpdb->prepare(
			$query,
			absint( $this->params['user'] ),
			current_time( 'mysql' )
		) );
	}

	/**
	 * Provides an array containing the value of the ATTENDEE_EVENT_KEY class constant
	 * for each active ticketing provider.
	 *
	 * @return array
	 */
	protected function get_event_keys() {
		$event_keys = array();

		foreach ( Tribe__Tickets__Tickets::modules() as $module_class => $module_instance ) {
			/**
			 * The usage of plain `$module_class::ATTENDEE_EVENT_KEY` will throw a `T_PAAMAYIM_NEKUDOTAYIM`
			 * when using PHP 5.2, which is a fatal.
			 *
			 * So we have to construct the constant name using a string and use the `constant` function.
			 */
			if ( defined( "$module_class::ATTENDEE_EVENT_KEY" ) ) {
				$event_keys[] = constant( "$module_class::ATTENDEE_EVENT_KEY" );
			} else {
				$event_keys[] = call_user_func( array( $module_class, 'get_key' ), 'ATTENDEE_EVENT_KEY' );
			}
		}

		return $event_keys;
	}

	/**
	 * Provides a quoted, comma separated and escaped list of meta keys used to link
	 * attendee posts to event posts.
	 *
	 * @return string
	 */
	protected function build_escaped_key_list( array $keys ) {
		global $wpdb;

		foreach ( $keys as &$key ) {
			$key = $wpdb->prepare( '%s', $key );
		}

		return implode( ',', $keys );
	}
}
