<?php

namespace Tribe\Tickets\Test\Traits;

use Tribe__Tickets__Commerce__PayPal__Main as PayPal;

;

use Tribe__Tickets__Data_API as Data_API;

trait With_PayPal {
	/**
	 * @before
	 */
	public function activate_paypal_as_provider(): void {
		// Ensure the PayPal module is active.
		add_filter( 'tribe_tickets_commerce_paypal_is_active', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ PayPal::class ] = tribe( PayPal::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}
}