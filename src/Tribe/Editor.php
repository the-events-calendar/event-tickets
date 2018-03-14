<?php

/**
 * Class Tribe__Tickets__Editor
 *
 * @since 4.7
 */
class Tribe__Tickets__Editor {

	/**
	 * Configure all action and filters user by this Class
	 *
	 * @since  4.7
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_post_capacity', tribe_callback( 'tickets.admin.views', 'template', 'editor/button-view-orders' ) );
		add_action( 'tribe_events_tickets_metabox_edit_main', array( $this, 'filter_get_price_fields' ), 10, 2 );
		add_action( 'tribe_events_tickets_capacity', tribe_callback( 'tickets.admin.views', 'template', 'editor/total-capacity' ) );
		add_action( 'tribe_events_tickets_ticket_table_add_header_column', tribe_callback( 'tickets.admin.views', 'template', 'editor/column-head-price' ) );
		add_action( 'tribe_events_tickets_ticket_table_add_tbody_column', array( $this, 'add_column_content_price' ), 10, 2 );
	}

	/**
	 * Prints and returns the Price fields
	 *
	 * @since  4.7
	 *
	 * @param  int  $post_id    Post ID
	 * @param  int  $ticket_id  Ticket ID
	 *
	 * @return string
	 */
	public function filter_get_price_fields( $post_id, $ticket_id ) {
		$context = array(
			'post_id'   => $post_id,
			'ticket_id' => $ticket_id,
		);

		return tribe( 'tickets.admin.views' )->template( 'editor/fieldset/price', $context );
	}

	/**
	 * Prints and returns the Body for the Price Column
	 *
	 * @since  4.6.2
	 *
	 * @param  Tribe__Tickets__Ticket_Object $ticket        Ticket object
	 * @param  mixed                         $provider_obj  The ticket provider object
	 *
	 * @return string
	 */
	public function add_column_content_price( $ticket, $provider_obj ) {
		$context = array(
			'ticket'       => $ticket,
			'provider_obj' => $provider_obj,
		);

		return tribe( 'tickets.admin.views' )->template( 'editor/column-body-price', $context );
	}
}