<?php

/**
 * Class Tribe__Tickets__Promoter__Observer
 *
 * Class used to observe hooks and actions happening to notify promoter of those actions.
 *
 * @since 4.10.1.1
 */
class Tribe__Tickets__Promoter__Observer {

	/**
	 * Holding the reference to the event post type
	 *
	 * @since 4.10.9
	 *
	 * @var string
	 */
	protected $event_type = 'tribe_events';

	/**
	 * Hooks on which this observer notifies promoter.
	 *
	 * @since 4.10.1.1
	 */
	public function hook() {
		/** @var Tribe__Promoter__PUE $pue */
		$pue = tribe( 'promoter.pue' );

		if ( ! $pue->has_license_key() ) {
			return;
		}

		/**
		 * In case the class for TEC is defined we use the value defined there
		 */
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$this->event_type = Tribe__Events__Main::POSTTYPE;
		}

		$this->registered_types();

		// Listen for changes on RSVP as Gutenberg Uses the post_type API to update RSVP's
		add_action( 'save_post_tribe_rsvp_tickets', [ $this, 'notify_rsvp_event' ], 10, 2 );

		// RSVP
		add_action( 'tickets_rsvp_ticket_deleted', [ $this, 'notify_event_id' ], 10, 2 );
		add_action( 'event_tickets_rsvp_tickets_generated', [ $this, 'notify_event_id' ], 10, 2 );

		// PayPal
		add_action( 'tickets_tpp_ticket_deleted', [ $this, 'notify_event_id' ], 10, 2 );
		add_action( 'event_tickets_tpp_tickets_generated', [ $this, 'notify_event_id' ], 10, 2 );

		// All tickets
		add_action( 'event_tickets_after_save_ticket', [ $this, 'notify' ], 10, 1 );

		// Actions from REST
		add_action( 'tribe_tickets_ticket_type_moved', [ $this, 'ticket_moved_type' ], 10, 4 );
	}

	/**
	 * Notify to the parent Event when an attendee has changes via REST API.
	 *
	 * @since 4.10.1.2
	 *
	 * @param $attendee_id
	 */
	public function notify_rsvp_event( $attendee_id ) {
		/** @var Tribe__Tickets__RSVP $provider */
		$provider = tribe_tickets_get_ticket_provider( $attendee_id );

		if ( ! $provider instanceof Tribe__Tickets__RSVP ) {
			return;
		}

		$this->notify( $provider->get_event_for_ticket( $attendee_id ) );
	}

	/**
	 * Attach hooks only if events has support for tickets, to the following actions:
	 *
	 * - `save_post_tribe_events`
	 * - `delete_post`
	 *
	 * @since 4.10.1.1
	 */
	public function registered_types() {

		if ( ! $this->event_support_tickets() ) {
			return;
		}

		add_action( 'save_post_' . $this->event_type, [ $this, 'notify' ], 10, 1 );
		add_action( 'delete_post', [ $this, 'delete_post' ], 10, 1 );
	}

	/**
	 * Check if the Event post type has support for tickets
	 *
	 * @since 4.10.9
	 *
	 * @return bool
	 */
	private function event_support_tickets() {
		$post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		return in_array( $this->event_type, $post_types, true );
	}

	/**
	 * Wrapper when the $post_id is passed as second argument of the hook.
	 *
	 * @since 4.10.1.1
	 *
	 * @param $ticket_id int The ID of the ticket.
	 * @param $event_id  int The ID of the post/event.
	 */
	public function notify_event_id( $ticket_id, $event_id ) {
		$this->notify( $event_id );
	}

	/**
	 * Action attached to tribe_tickets_ticket_type_moved to notify promoter when a ticket is moved.
	 *
	 * @since 4.10.1.2
	 *
	 * @param int $ticket_type_id
	 * @param int $destination_id
	 * @param int $source_id
	 * @param int $instigator_id
	 */
	public function ticket_moved_type( $ticket_type_id, $destination_id, $source_id, $instigator_id ) {
		$this->notify( $source_id );
		// Prevent to send the same response twice if the ID's are the same.
		if ( $source_id !== $destination_id ) {
			$this->notify( $destination_id );
		}
	}

	/**
	 * Notify the connector of changes when the event was deleted
	 *
	 * @since 4.10.9
	 *
	 * @param $post_id
	 */
	public function delete_post( $post_id ) {
		if ( $this->event_type === get_post_type( $post_id ) ) {
			$this->notify( $post_id );
		}
	}

	/**
	 * Function used to notify the promoter endpoint of a new change on an event.
	 *
	 * @since 4.10.1.1
	 *
	 * @param $post_id int The ID of the post.
	 */
	public function notify( $post_id ) {
		try {
			/** @var Tribe__Promoter__Connector $connector */
			$connector = tribe( 'promoter.connector' );
			$connector->notify_promoter_of_changes( $post_id );
		} catch ( RuntimeException $exception ) {
			// TODO: Report this to the logger
			return;
		}
	}
}