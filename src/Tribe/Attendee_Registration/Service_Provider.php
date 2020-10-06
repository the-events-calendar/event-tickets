<?php

class Tribe__Tickets__Attendee_Registration__Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the Attendee Info Provider singletons.
	 *
	 * @since 4.9
	 */
	public function register() {
		$this->container->singleton( 'tickets.attendee_registration', Tribe__Tickets__Attendee_Registration__Main::class );
		$this->container->singleton( 'tickets.attendee_registration.template', Tribe__Tickets__Attendee_Registration__Template::class );
		$this->container->singleton( 'tickets.attendee_registration.view', Tribe__Tickets__Attendee_Registration__View::class );
		$this->container->singleton( 'tickets.attendee_registration.rewrite', Tribe__Tickets__Attendee_Registration__Rewrite::class );
		$this->container->singleton( 'tickets.attendee_registration.meta', Tribe__Tickets__Attendee_Registration__Meta::class );
		$this->container->singleton( 'tickets.attendee_registration.shortcode', Tribe__Tickets__Attendee_Registration__Shortcode::class );
		$this->container->singleton( 'tickets.attendee_registration.modal', Tribe__Tickets__Attendee_Registration__Modal::class );

		// Priority 45 to be before priority 50 used on the same action in hooks().
		add_action( 'tribe_plugins_loaded', [ $this, 'hooks' ], 45 );
	}

	/**
	 * Add actions and filters for the Attendee Info classes.
	 *
	 * @since 4.9
	 * @since TBD Bail if ETP is not active.
	 */
	public function hooks() {
		// Only run the code if Event Tickets Plus is active.
		if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
			return;
		}

		// After priority 45 from register() using this same action.
		add_action( 'tribe_plugins_loaded', [ $this, 'add_attendee_registration_template_hook' ], 50 );

		add_action( 'init', [ $this, 'add_attendee_registration_shortcode_hook' ] );
		add_action( 'init', [ $this, 'add_attendee_registration_modal_hook' ] );
		add_action( 'init', [ $this, 'add_rewrite_tags' ] );
		add_action( 'tribe_tickets_pre_rewrite', [ $this, 'generate_core_rules' ] );

		add_filter( 'generate_rewrite_rules', [ $this, 'filter_generate' ] );
		add_filter( 'rewrite_rules_array', [ $this, 'remove_percent_placeholders' ], 25 );
		add_filter( 'tribe_tickets_commerce_paypal_add_to_cart_args', [ $this, 'add_product_delete_to_paypal_url' ], 10, 1 );
	}

	public function add_attendee_registration_template_hook() {
		/** @var Tribe__Tickets__Attendee_Registration__Template $make */
		$make = $this->container->make( 'tickets.attendee_registration.template' );

		$make->hook();
	}

	public function add_attendee_registration_shortcode_hook() {
		/** @var Tribe__Tickets__Attendee_Registration__Shortcode $make */
		$make = $this->container->make( 'tickets.attendee_registration.shortcode' );

		$make->hook();
	}

	public function add_attendee_registration_modal_hook() {
		/** @var Tribe__Tickets__Attendee_Registration__Modal $make */
		$make = $this->container->make( 'tickets.attendee_registration.modal' );

		$make->hook();
	}

	public function add_rewrite_tags() {
		/** @var Tribe__Tickets__Attendee_Registration__Rewrite $make */
		$make = $this->container->make( 'tickets.attendee_registration.rewrite' );

		$make->add_rewrite_tags();
	}

	/**
	 * @param Tribe__Tickets__Attendee_Registration__Rewrite $rewrite
	 */
	public function generate_core_rules( Tribe__Tickets__Attendee_Registration__Rewrite $rewrite ) {
		/** @var Tribe__Tickets__Attendee_Registration__Rewrite $make */
		$make = $this->container->make( 'tickets.attendee_registration.rewrite' );

		$make->generate_core_rules( $rewrite );
	}

	/**
	 * @param WP_Rewrite $wp_rewrite
	 */
	public function filter_generate( WP_Rewrite $wp_rewrite ) {
		/** @var Tribe__Tickets__Attendee_Registration__Rewrite $make */
		$make = $this->container->make( 'tickets.attendee_registration.rewrite' );

		$make->filter_generate( $wp_rewrite );
	}

	/**
	 * @param array $rules
	 *
	 * @return array
	 */
	public function remove_percent_placeholders( array $rules ) {
		/** @var Tribe__Tickets__Attendee_Registration__Rewrite $make */
		$make = $this->container->make( 'tickets.attendee_registration.rewrite' );

		return $make->remove_percent_placeholders( $rules );
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	public function add_product_delete_to_paypal_url( $args ) {
		/** @var Tribe__Tickets__Attendee_Registration__Meta $make */
		$make = $this->container->make( 'tickets.attendee_registration.meta' );

		return $make->add_product_delete_to_paypal_url( $args );
	}
}
