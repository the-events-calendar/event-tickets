<?php

/**
 * Class Tribe__Tickets__Promoter_Observer
 *
 * Class used to observe hooks and actions happening to notify promoter of those actions.
 *
 * @since TBD
 */
class Tribe__Tickets__Promoter_Observer {

	/**
	 * Hooks on which this obseverver notifies promoter
	 *
	 * @since TBD
	 */
	public function hook() {
		$this->registered_types();
		// RSVP
		add_action( 'event_tickets_rsvp_ticket_created', array( $this, 'notify_event_id' ), 10, 2 );
		add_action( 'tickets_rsvp_ticket_deleted', array( $this, 'notify_event_id' ), 10, 2 );
		add_action( 'event_tickets_rsvp_tickets_generated', array( $this, 'notify_event_id' ), 10, 2 );
		// Paypal
		add_action( 'tickets_tpp_ticket_deleted', array( $this, 'notify_event_id' ), 10, 2 );
		add_action( 'event_tickets_tpp_tickets_generated', array( $this, 'notify_event_id' ), 10, 2 );
		// All tickets
		add_action( 'event_tickets_after_save_ticket', array( $this, 'notify' ), 10, 1 );
		add_action( 'tribe_tickets_ticket_add', array( $this, 'notify' ), 10, 1 );
		// Actions from REST
		add_action( 'tribe_tickets_ticket_added', array( $this, 'notify' ), 10, 1 );
		add_action( 'tribe_tickets_ticket_deleted', array( $this, 'notify' ), 10, 1 );
	}

	/**
	 * Attach support to the registered types, right now only has support for events, in order
	 * to attach a hook to `save_post_tribe_events` only attaches the action
	 * if the post has supoort for tickets or if the support has been enabled for this type.
	 *
	 * @since TBD
	 */
	public function registered_types() {
		$event_type = class_exists( 'Tribe__Events__Main' )
			? Tribe__Events__Main::POSTTYPE
			: 'tribe_events';
		$post_types = (array) tribe_get_option( 'ticket-enabled-post-types', array() );

		$events_support_tickets = in_array( $event_type, $post_types, true  );

		if ( ! $events_support_tickets ) {
			return;
		}

		add_action( 'save_post_' . $event_type, array( $this, 'notify' ), 10, 1 );
	}

	/**
	 * Wrapper when the $post_id is passed as second argument of the hook
	 *
	 * @since TBD
	 *
	 * @param $ticket_id int The ID of the ticket
	 * @param $event_id int The ID of the post/event
	 */
	public function notify_event_id( $ticket_id, $event_id ) {
		$this->notify( $event_id );
	}

	/**
	 * Function used to notify the promoter endpoint of a new change on an event
	 *
	 * @param $post_id int The ID of the post
	 */
	public function notify( $post_id ) {
		try {
			/** @var Tribe__Promoter__Connector $connector */
			$connector = tribe( 'promoter.connector' );
			$connector->notify_promoter_of_changes( $post_id );
		} catch( RuntimeException $exception ) {
			// TODO: Report this to the logger
			return;
		}
	}
}