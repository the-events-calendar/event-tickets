<?php

class Tribe__Tickets__Attendee_Info__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the Attendee Info Provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		tribe_singleton( 'tickets.attendee_info.view', 'Tribe__Tickets__Attendee_Info__View' );
		tribe_singleton( 'tickets.attendee_info.rewrite', 'Tribe__Tickets__Attendee_Info__Rewrite' );
		tribe_singleton( 'tickets.attendee_info.meta', 'Tribe__Tickets__Attendee_Info__Meta' );

		$this->register_hooks();
	}

	/**
	 * Add actions and filters for the Attendee Info classes.
	 *
	 * @since TBD
	 */
	protected function register_hooks() {
		add_action( 'template_redirect', array( tribe( 'tickets.attendee_info.view' ), 'display_attendee_info_page' ) );
		add_action( 'tribe_tickets_pre_rewrite', array( tribe( 'tickets.attendee_info.rewrite' ), 'generate_core_rules' ) );
		add_action( 'init', array( tribe( 'tickets.attendee_info.rewrite' ), 'add_rewrite_tags' ) );
		add_filter( 'generate_rewrite_rules', array( tribe( 'tickets.attendee_info.rewrite' ), 'filter_generate' ) );
		add_filter( 'rewrite_rules_array', array( tribe( 'tickets.attendee_info.rewrite' ), 'remove_percent_placeholders' ), 25 );
		add_filter( 'event_tickets_plus_meta_fields_by_ticket', array( tribe( 'tickets.attendee_info.meta' ), 'add_pii_fields_to_attendee' ), 10, 2 );

		// attendee updating
//		'event_tickets_tpp_attendee_created',
//		'event_tickets_edd_ticket_created',
//		'event_tickets_woocommerce_ticket_created',
	}

}
