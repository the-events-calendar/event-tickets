<?php

namespace Tribe\Tickets\RSVP;

use Tribe__Tickets__Main;

/**
 * Class Early_Access
 *
 * Handles Early Access for the new RSVP template.
 *
 * @since TBD
 *
 * @package Tribe\Tickets\RSVP
 */
class Early_Access {

	/**
	 * Name of option that holds whether to use RSVP Early Access.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected static $option_rsvp_early_access = 'tribe_tickets_rsvp_early_access';

	/**
	 * Returns whether we are using RSVP Early Access or not.
	 *
	 * @since TBD
	 *
	 * @return bool Whether it's early access or not.
	 */
	public function is_rsvp_early_access() {
		return (bool) get_option( self::$option_rsvp_early_access, false );
	}

	/**
	 * Set the option to whether use RSVP Early Access or not.
	 *
	 * @since TBD
	 *
	 * @param bool $is_early_access
	 *
	 * @return bool Whether the update was successful or not.
	 */
	public function set_rsvp_early_access( bool $is_early_access ) {
		return update_option( self::$option_rsvp_early_access, $is_early_access );
	}

	/**
	 * Maybe registers Early Access assets
	 *
	 * @since TBD
	 *
	 * @action init 10
	 * @see \Tribe\Tickets\RSVP\Service_Provider::register_early_access
	 */
	public function maybe_register_early_access_assets() {
		// Early bail: Nothing to do if not using Early Access.
		if ( ! $this->is_rsvp_early_access() ) {
			return;
		}

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
	public function maybe_deregister_rsvp_assets() {
		// Early bail: Nothing to do if not using Early Access.
		if ( ! $this->is_rsvp_early_access() ) {
			return;
		}

		/* @see \Tribe__Tickets__RSVP::register_resources */
		wp_deregister_style( 'event-tickets-rsvp' );
		wp_deregister_script( 'event-tickets-rsvp' );

		/* @see \Tribe__Tickets__Assets::enqueue_scripts */
		wp_deregister_style( 'event-tickets-tickets-rsvp-css' );
		wp_deregister_script( 'event-tickets-tickets-rsvp-js' );
	}

	/**
	 * Changes the RSVP template if in Early Access
	 *
	 * @param string $file The template file being filtered.
	 *
	 * @since TBD
	 *
	 * @filter tribe_events_tickets_template_tickets/rsvp 10 1
	 * @see \Tribe\Tickets\RSVP\Service_Provider::register_early_access
	 * @see \Tribe__Tickets__Tickets::getTemplateHierarchy
	 *
	 * @return string
	 */
	public function maybe_override_template( $file ) {
		// Early bail: Nothing to do if not using Early Access.
		if ( ! $this->is_rsvp_early_access() ) {
			return $file;
		}

		$file = str_replace( 'rsvp.php', 'rsvp-early-access.php', $file );

		return $file;
	}
}