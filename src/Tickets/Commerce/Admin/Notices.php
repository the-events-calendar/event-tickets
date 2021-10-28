<?php

namespace TEC\Tickets\Commerce\Admin;

use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Success;
use \Tribe__Settings;

/**
 * Class Notices
 *
 * @since TBD
 * 
 * @package TEC\Tickets\Commerce\Admin
 */
class Notices {

	/**
	 * @inheritdoc
	 */
	public function hook() {
		add_action( 'admin_init', [ $this, 'maybe_display_notices' ] );
	}

	/**
	 * @inheritdoc
	 */
	public function maybe_display_notices() {
		$this->maybe_display_checkout_setting_notice();
		$this->maybe_display_success_setting_notice();
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, yet a checkout page is not setup properly
	 *
	 * @since TBD
	 */
	public function maybe_display_checkout_setting_notice() {
		// If we're not on our own settings page, bail.
		if ( Tribe__Settings::$parent_slug !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		tribe_notice(
			'event-tickets-tickets-commerce-checkout-not-set',
			[ tribe( Checkout::class ), 'unset_notice' ],
			[ 'dismiss' => false, 'type' => 'error' ],
			[ tribe( Checkout::class ), 'is_unset' ]
		);
	}

	/**
	 * Display a notice when Tickets Commerce is enabled, yet a success page is not setup properly
	 *
	 * @since TBD
	 */
	public function maybe_display_success_setting_notice() {
		// If we're not on our own settings page, bail.
		if ( Tribe__Settings::$parent_slug !== tribe_get_request_var( 'page' ) ) {
			return;
		}

		tribe_notice(
			'event-tickets-tickets-commerce-success-not-set',
			[ tribe( Success::class ), 'unset_notice' ],
			[ 'dismiss' => false, 'type' => 'error' ],
			[ tribe( Success::class ), 'is_unset' ]
		);
	}
}
