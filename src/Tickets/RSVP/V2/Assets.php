<?php
/**
 * Handles registering and setup for assets on RSVP V2.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */

namespace TEC\Tickets\RSVP\V2;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Tickets__Main;

/**
 * Class Assets.
 *
 * Registers RSVP-specific assets for the V2 implementation.
 * V2 RSVP uses TC (Tickets Commerce) infrastructure, so most assets
 * are inherited from TC. This class handles RSVP-specific additions.
 *
 * @since TBD
 *
 * @package TEC\Tickets\RSVP\V2
 */
class Assets extends Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register(): void {
		/** @var Tribe__Tickets__Main $tickets_main */
		$tickets_main = tribe( 'tickets.main' );

		// Register RSVP V2 specific assets.
		// Note: V2 RSVP uses TC infrastructure, so most assets are provided by TC.
		// This file registers RSVP-specific additions if needed.

		/**
		 * Fires after RSVP V2 assets have been registered.
		 *
		 * @since TBD
		 *
		 * @param Assets             $assets       The assets instance.
		 * @param Tribe__Tickets__Main $tickets_main The main tickets instance.
		 */
		do_action( 'tec_tickets_rsvp_v2_assets_registered', $this, $tickets_main );
	}
}
