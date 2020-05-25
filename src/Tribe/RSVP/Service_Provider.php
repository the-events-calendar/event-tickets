<?php

namespace Tribe\Tickets\RSVP;

/**
 * Class Service_Provider
 *
 * @since TBD
 *
 * @package Tribe\Tickets\RSVP
 */
class Service_Provider extends \tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 *
	 * @since TBD
	 */
	public function register() {
		$this->register_early_access();
	}

	/**
	 * Registers the RSVP Early Access Service.
	 *
	 * @since TBD
	 */
	private function register_early_access() {
		add_action( 'init', [
			$this->container->make( Early_Access::class ),
			'maybe_register_early_access_assets'
		]);

		add_action( 'wp_enqueue_scripts', [
			$this->container->make( Early_Access::class ),
			'maybe_deregister_rsvp_assets'
		], 100 );

		add_filter( 'tribe_events_tickets_template_tickets/rsvp.php', [
			$this->container->make( Early_Access::class ),
			'maybe_override_template'
		] );
	}
}