<?php


/**
 * Class Tribe__Tickets__Capabilities
 *
 * @since TBD
 *
 */
class Tribe__Tickets__Capabilities {

	public $set_initial_caps = false;

	private $cap_aliases = array(
		'editor' => array( // full permissions to a post type
		                   'manage_attendees',
		                   'checkin_attendees',
		),
		'author' => array( // full permissions for content the user created
		                   'checkin_attendees',
		),
	);

	/**
	 * Grant caps for the given post type to the given role
	 *
	 * @param string $role_id The role receiving the caps
	 * @param string $level   The capability level to grant (see the list of caps above)
	 *
	 * @return bool false if the action failed for some reason, otherwise true
	 */
	public function register_caps( $role_id, $level = '' ) {
		if ( empty( $level ) ) {
			$level = $role_id;
		}

		if ( 'administrator' === $level ) {
			$level = 'editor';
		}

		if ( ! isset( $this->cap_aliases[ $level ] ) ) {
			return false;
		}

		$role = get_role( $role_id );
		if ( ! $role ) {
			return false;
		}

		foreach ( $this->cap_aliases[ $level ] as $alias ) {
			$role->add_cap( $alias );
		}

		return true;
	}

	/**
	 * Remove all caps for the given post type from the given role
	 *
	 * @param string $post_type The post type to remove caps for
	 * @param string $role_id   The role which is losing caps
	 *
	 * @return bool false if the action failed for some reason, otherwise true
	 */
	public function remove_post_type_caps( $role_id ) {
		$role = get_role( $role_id );
		if ( ! $role ) {
			return false;
		}
		foreach ( $role->capabilities as $cap => $has ) {
			$role->remove_cap( $cap );
		}

		return true;
	}

	/**
	 * Set the initial capabilities for events and related post types on default roles
	 *
	 * @return void
	 */
	public function set_initial_caps() {
		// this is a flag for testing purposes to make sure this function is firing
		$this->set_initial_caps = true;
		foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
			$this->register_caps( $role );
		}
	}

	/**
	 * Remove capabilities for events and related post types from default roles
	 *
	 * @return void
	 */
	public function remove_all_caps() {
		foreach ( array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ) as $role ) {
			//	$this->remove_post_type_caps( $role );
		}
	}

	/**
	 * Check if User Has Capability to Display a Attendee Action
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function check_manage_capability( $action = false ) {

		if ( $action && ! current_user_can( 'manage_attendees' ) ) {
			wp_die( '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to manage Attendees.', 'event-tickets' ) . '</p>', 403 );
		}

		if ( current_user_can( 'manage_attendees' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if User Has Capability to Display a Attendee Action
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function check_checkin_capability( $action = false ) {

		if ( $action && ! current_user_can( 'checkin_attendees' ) && ! current_user_can( 'manage_attendees' ) ) {
			wp_die( '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' . '<p>' . __( 'Sorry, you are not allowed to manage Attendees.', 'event-tickets' ) . '</p>', 403 );
		}

		if ( current_user_can( 'checkin_attendees' ) || current_user_can( 'manage_attendees' ) ) {
			return true;
		}

		return false;
	}
}
