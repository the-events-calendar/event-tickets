<?php

/**
 * Initialize template overwrite for tickets block
 *
 * @since TBD
 */
class Tribe__Tickets__Editor__Template__Overwrite {

	/**
	 * Variable that is used to reference to the current ticket provider like: WOO, EDD, RSVP
	 *
	 * @since TBD
	 *
	 * @var null
	 */
	protected $ticket_type = null;

	/**
	 * Hook into the Events Template single page to allow Blocks to be properly reordered
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'the_post', array( $this, 'include_blocks_in_other_types' ) );
		add_action( 'tribe_pre_get_view', array( $this, 'include_blocks_in_events' ) );
		add_action( 'tribe_tickets_tickets_hook', array( $this, 'tickets_hook' ) );
	}

	/**
	 * Create a new instance variable used to reference to the current ticket provider
	 *
	 * @since TBD
	 *
	 * @param Tribe__Tickets__Tickets $ticket_type
	 */
	public function tickets_hook( $ticket_type ) {
		$this->ticket_type = $ticket_type;
	}

	/**
	 * After `the_post` try to setup the template used by tickets, rsvp and attendees views on
	 * post that are not events
	 *
	 * @since TBD
	 */
	public function include_blocks_in_other_types() {
		$post_id = get_the_ID();
		if (
			false === $post_id
			|| ! has_blocks( $post_id )
			|| ! $this->should_inject_tickets_in_other_types( $post_id )
		) {
			return;
		}
		$this->remove_classic_views();
		$this->setup_template( $post_id );
	}

	/***
	 * After `tribe_pre_get_view` try to setup the template used by tickets, rsvp and attendees views on
	 * events
	 *
	 * @since TBD
	 */
	public function include_blocks_in_events() {
		$post_id = get_the_ID();
		if (
			false === $post_id
			|| ! has_blocks( $post_id )
			|| ! $this->should_inject_tickets_in_events( $post_id )
		) {
			return;
		}
		$this->setup_template( $post_id );
	}

	/**
	 * Check if a post from different type than an event should be injected with the variables
	 * used to setup the views for the tickets
	 *
	 * @since TBD
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function should_inject_tickets_in_other_types( $post_id ) {
		// Make sure this executed inside of the loop to prevent multiple requests and false positives of IDs
		$in_the_loop = isset( $GLOBALS['wp_query']->in_the_loop ) && $GLOBALS['wp_query']->in_the_loop;

		if ( empty( $post_id ) || is_admin() || ! $in_the_loop ) {
			return false;
		}

		return $this->has_tickets_support( $post_id );
	}

	/**
	 * Check if an event should be injected with the template variables
	 *
	 * @since TBD
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function should_inject_tickets_in_events( $post_id ) {
		if ( empty( $post_id ) || is_admin() ) {
			return false;
		}

		return $this->has_tickets_support( $post_id );
	}

	/**
	 * Check if the post / event has support tickets and has tickets available
	 *
	 * @since TBD
	 *
	 * @param $post_id
	 *
	 * @return bool
	 */
	public function has_tickets_support( $post_id ) {
		$post_type = get_post_type( $post_id );

		if ( ! tribe_tickets_post_type_enabled( $post_type ) ) {
			return false;
		}

		//  User is currently viewing/editing their existing tickets.
		if ( Tribe__Tickets__Tickets_View::instance()->is_edit_page() ) {
			return false;
		}

		// if there aren't any tickets, bail
		$tickets = Tribe__Tickets__Tickets::get_event_tickets( $post_id );

		return ! empty( $tickets );
	}

	/**
	 * Set template variables used by the tickets, RSVP and attendees blocks
	 *
	 * @since TBD
	 *
	 * @param $post_id
	 */
	public function setup_template( $post_id ) {
		tribe( 'tickets.editor.template' )->add_template_globals( array(
			'post_id' => $post_id,
			'post'    => get_post( $post_id ),
		) );
	}

	/**
	 * Remove the actions and filters used to attach the classic editor views associated with
	 * tickets
	 *
	 * @since TBD
	 */
	public function remove_classic_views() {
		if ( ! ( $this->ticket_type instanceof Tribe__Tickets__Tickets ) ) {
			return;
		}

		$ticket_form_hook = $this->ticket_type->get_ticket_form_hook();
		if ( ! empty( $ticket_form_hook ) ) {
			remove_action( $ticket_form_hook, array(
				$this->ticket_type,
				'maybe_add_front_end_tickets_form'
			), 5 );
		}

		remove_filter( 'the_content', array(
			$this->ticket_type,
			'front_end_tickets_form_in_content'
		), 11 );
	}
}