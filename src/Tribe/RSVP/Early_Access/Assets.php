<?php

namespace Tribe\Tickets\RSVP\Early_Access;

use Tribe__Tickets__Main;

class Assets {

	/**
	 * Maybe registers Early Access assets
	 *
	 * @since TBD
	 *
	 * @action init 10
	 * @see \Tribe\Tickets\RSVP\Service_Provider::register_early_access
	 */
	public function register_early_access_assets() {
		tribe_asset(
			Tribe__Tickets__Main::instance(),
			'event-tickets-rsvp-early-access-styles',
			'rsvp-early-access.css',
			[],
			null,
			[]
		);

		tribe_asset(
			Tribe__Tickets__Main::instance(),
			'event-tickets-rsvp-early-access-scripts',
			'rsvp-early-access.js',
			[ 'jquery', 'wp-util' ],
			null,
			[]
		);
	}

	/**
	 * Maybe de-registers non-early access RSVP assets
	 *
	 * @since TBD
	 *
	 * @action wp_enqueue_scripts 100
	 * @see \Tribe\Tickets\RSVP\Service_Provider::register_early_access
	 */
	public function deregister_rsvp_assets() {
		/* @see \Tribe__Tickets__RSVP::register_resources */
		wp_deregister_style( 'event-tickets-rsvp' );
		wp_deregister_script( 'event-tickets-rsvp' );

		/* @see \Tribe__Tickets__Assets::enqueue_scripts */
		wp_deregister_style( 'event-tickets-tickets-rsvp-css' );
		wp_deregister_script( 'event-tickets-tickets-rsvp-js' );
	}
}