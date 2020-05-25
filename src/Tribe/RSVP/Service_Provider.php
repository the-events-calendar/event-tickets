<?php

namespace Tribe\Tickets\RSVP;

use Tribe\Tickets\RSVP\Early_Access\Assets;
use Tribe\Tickets\RSVP\Early_Access\Early_Access;
use Tribe\Tickets\RSVP\Early_Access\Template;
use Tribe\Tickets\RSVP\Early_Access\Update_Notice;

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
		// Early bail: RSVP Early Access not enabled.
		if ( ! $this->container->make( Early_Access::class )->is_rsvp_early_access() ) {
			return;
		}

		add_action( 'init', [
			$this->container->make( Assets::class ),
			'register_early_access_assets',
		] );

		add_action( 'wp_enqueue_scripts', [
			$this->container->make( Assets::class ),
			'deregister_rsvp_assets',
		], 100 );

		add_filter( 'tribe_events_tickets_template_tickets/rsvp.php', [
			$this->container->make( Template::class ),
			'override_template',
        ] );
        
		add_action( 'admin_init', [
			$this->container->make( Update_Notice::class ),
			'maybe_display_update_notice',
		] );
	}
}