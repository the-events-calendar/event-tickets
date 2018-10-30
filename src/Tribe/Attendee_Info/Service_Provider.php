<?php

class Tribe__Tickets__Attendee_Info__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the Attendee Info Provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {
		tribe_singleton( 'tickets.attendee_info.template', 'Tribe__Tickets__Attendee_Info__Template' );
		tribe_singleton( 'tickets.attendee_info.view', 'Tribe__Tickets__Attendee_Info__View' );
		tribe_singleton( 'tickets.attendee_info.rewrite', 'Tribe__Tickets__Attendee_Info__Rewrite' );
		tribe_singleton( 'tickets.attendee_info.meta', 'Tribe__Tickets__Attendee_Info__Meta' );

		$this->hooks();
	}

	/**
	 * Add actions and filters for the Attendee Info classes.
	 *
	 * @since TBD
	 */
	protected function hooks() {

		add_action( 'plugins_loaded', array( tribe( 'tickets.attendee_info.template' ), 'hook' ) );
		add_action( 'tribe_tickets_pre_rewrite', array( tribe( 'tickets.attendee_info.rewrite' ), 'generate_core_rules' ) );
		add_action( 'init', array( tribe( 'tickets.attendee_info.rewrite' ), 'add_rewrite_tags' ) );
		add_filter( 'generate_rewrite_rules', array( tribe( 'tickets.attendee_info.rewrite' ), 'filter_generate' ) );
		add_filter( 'rewrite_rules_array', array( tribe( 'tickets.attendee_info.rewrite' ), 'remove_percent_placeholders' ), 25 );
		add_filter( 'event_tickets_plus_meta_fields_by_ticket', array( tribe( 'tickets.attendee_info.meta' ), 'add_pii_fields_to_attendee' ), 10, 2 );
		add_filter( 'tribe_tickets_commerce_paypal_add_to_cart_args', array( tribe( 'tickets.attendee_info.meta' ), 'add_product_delete_to_paypal_url' ), 10, 1 );
	}

}
