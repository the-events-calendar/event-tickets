<?php

namespace Tribe\Tickets\Test\Traits;

use TEC\Tickets\Commerce\Module;
use Tribe__Tickets__Data_API as Data_API;
use TEC\Tickets\Commerce\Provider as Commerce_Provider;

trait With_Tickets_Commerce {
	/**
	 * @before
	 */
	public function activate_tickets_commerce_as_provider(): void {
		add_filter( 'tribe_tickets_get_modules', static function ( $modules ) {
			$modules[ Module::class ] = tribe( Module::class )->plugin_name;

			return $modules;
		} );

		// Reset Data_API object, so it sees Tribe Commerce.
		tribe_singleton( 'tickets.data_api', new Data_API );

		// Ensure `post` is a ticketable post type.
		$ticketable   = tribe_get_option( 'ticket-enabled-post-types', [] );
		$ticketable[] = 'post';
		tribe_update_option( 'ticket-enabled-post-types', array_values( array_unique( $ticketable ) ) );

		$commerce_provider = tribe( Commerce_Provider::class );
		$commerce_provider->run_init_hooks();
	}
}
