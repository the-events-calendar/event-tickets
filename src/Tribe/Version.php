<?php
/**
 * Handling of Ticket Versioning
 *
 * @container tickets.version
 * @since  TBD
 */
class Tribe__Tickets__Version {

	/**
	 * Prior to this version we didn't have Versions for Tickets
	 *
	 * @since  TBD
	 *
	 * @var    string
	 */
	public $legacy = '4.5.6';

	/**
	 * Post meta key for the ticket version
	 *
	 * @since  TBD
	 *
	 * @var    string
	 */
	public $meta_key = '_tribe_ticket_version';

	public function __construct( $hook = true ) {
		if ( ! $hook ) {
			return;
		}

		add_action( 'tribe_tickets_ticket_add', array( $this, 'on_load' ) );
	}

	public function on_load() {

	}

	/**
	 * Checks if the Post meta exists
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket  Which ticket
	 *
	 * @return bool
	 */
	public function exists( $ticket ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		return metadata_exists( 'post', $ticket->ID, $this->meta_key );
	}

	/**
	 * Updates ticket version to a given string
	 * Will default to current version
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket   Which ticket
	 * @param  null|string  $version  Version to update to (optional)
	 *
	 * @return bool
	 */
	public function update( $ticket, $version = null ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		if ( empty( $version ) ) {
			$version = Tribe__Tickets__Main::VERSION;
		}

		return update_post_meta( $ticket->ID, $this->meta_key, $version );
	}

	/**
	 * Fetches the ticket version number
	 *
	 * Assumes legacy version when Non Existent meta
	 * Assumes current version when Meta is Empty
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket  Which ticket
	 *
	 * @return bool
	 */
	public function get( $ticket ) {
		if ( ! $ticket instanceof WP_Post ) {
			$ticket = get_post( $ticket );
		}

		if ( ! $ticket instanceof WP_Post ) {
			return false;
		}

		// It means it was a legacy ticket, set it to the one before
		if ( ! $this->exists( $ticket ) ) {
			$version = $this->legacy;
		} else {
			$version = get_post_meta( $ticket->ID, $this->meta_key, true );
		}

		// Defaults to current version
		if ( empty( $version ) ) {
			$version = Tribe__Tickets__Main::VERSION;
		}

		return $version;
	}

	/**
	 * Version compare a ticket version to a given string
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket   Which ticket
	 * @param  null|string  $version  Version to compare to
	 * @param  string       $compare  What operator is passed to `version_compare()` (optional)
	 *
	 * @return bool
	 */
	public function compare( $ticket, $version, $compare = '>' ) {
		$ticket_version = $this->get( $ticket );

		return version_compare( $version, $ticket_version, $compare );
	}

	/**
	 * Checks if a given ticket is from a legacy version
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket  Which ticket
	 *
	 * @return bool
	 */
	public function is_legacy( $ticket ) {
		return $this->compare( $ticket, $this->legacy, '<=' );
	}

	/**
	 * Checks if a given ticket was not updated on the latest version
	 *
	 * @since  TBD
	 *
	 * @param  int|WP_Post  $ticket  Which ticket
	 *
	 * @return bool
	 */
	public function is_outdated( $ticket ) {
		return $this->compare( $ticket, Tribe__Tickets__Main::VERSION, '<' );
	}
}