<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce;

use tad_DI52_ServiceProvider;
use Tribe__Tickets__Main;

/**
 * Service provider for the Tickets Commerce.
 *
 * @since   TBD
 * @package Tribe\Tickets\Commerce\Tickets_Commerce
 */
class Service_Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since TBD
	 */
	public function register() {

		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since TBD
	 */
	protected function hooks() {
		add_action( 'admin_init', [ $this, 'register_assets' ] );
	}

	public function register_assets() {
		tribe_asset(
			Tribe__Tickets__Main::instance(),
			'tribe-tickets-admin-commerce-settings',
			'admin/tickets-commerce-settings.js',
			[ 'jquery' ],
			'admin_enqueue_scripts'
		);
	}

}
