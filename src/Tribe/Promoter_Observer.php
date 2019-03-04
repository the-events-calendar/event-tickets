<?php
class Tribe__Tickets__Promoter_Observer {
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

	public function notify_event_id( $ticket_id, $event_id ) {
		$this->notify( $event_id );
	}

	public function notify( $post_id ) {
		/** @var Tribe__Promoter__Connector $connector */
		$connector = tribe( 'promoter.connector' );
		$connector->notify_promoter_of_changes( $post_id );
	}
}