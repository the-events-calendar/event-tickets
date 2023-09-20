<?php

namespace Tribe\Tickets\Test\Traits;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Data_API as Data_API;

trait With_Tickets_Commerce {
	/**
	 * @before
	 */
	public function activate_tickets_commerce_as_provider(): void {
		// Ensure the Tickets Commerce module is active.
		add_filter( 'tec_tickets_commerce_is_enabled', '__return_true' );
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );
	}
}