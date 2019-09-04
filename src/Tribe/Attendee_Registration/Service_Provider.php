<?php

class Tribe__Tickets__Attendee_Registration__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the Attendee Info Provider singletons.
	 *
	 * @since 4.9
	 */
	public function register() {
		tribe_singleton( 'tickets.attendee_registration', 'Tribe__Tickets__Attendee_Registration__Main' );
		tribe_singleton( 'tickets.attendee_registration.template', 'Tribe__Tickets__Attendee_Registration__Template' );
		tribe_singleton( 'tickets.attendee_registration.view', 'Tribe__Tickets__Attendee_Registration__View' );
		tribe_singleton( 'tickets.attendee_registration.rewrite', 'Tribe__Tickets__Attendee_Registration__Rewrite' );
		tribe_singleton( 'tickets.attendee_registration.meta', 'Tribe__Tickets__Attendee_Registration__Meta' );
		tribe_singleton( 'tickets.attendee_registration.shortcode', 'Tribe__Tickets__Attendee_Registration__Shortcode' );
		tribe_singleton( 'tickets.attendee_registration.modal', 'Tribe__Tickets__Attendee_Registration__Modal' );

		$this->hooks();
	}

	/**
	 * Add actions and filters for the Attendee Info classes.
	 *
	 * @since 4.9
	 */
	protected function hooks() {
		add_action( 'plugins_loaded', tribe_callback( 'tickets.attendee_registration.template', 'hook' ) );
		add_action( 'init', tribe_callback( 'tickets.attendee_registration.shortcode', 'hook' ) );
		add_action( 'init', tribe_callback( 'tickets.attendee_registration.modal', 'hook' ) );
		add_action( 'tribe_tickets_pre_rewrite', tribe_callback( 'tickets.attendee_registration.rewrite', 'generate_core_rules' ) );
		add_action( 'init', tribe_callback( 'tickets.attendee_registration.rewrite', 'add_rewrite_tags' ) );
		add_filter( 'generate_rewrite_rules', tribe_callback( 'tickets.attendee_registration.rewrite', 'filter_generate' ) );
		add_filter( 'rewrite_rules_array', tribe_callback( 'tickets.attendee_registration.rewrite', 'remove_percent_placeholders' ), 25 );
		add_filter( 'tribe_tickets_commerce_paypal_add_to_cart_args', tribe_callback( 'tickets.attendee_registration.meta', 'add_product_delete_to_paypal_url' ), 10, 1 );
	}

}
