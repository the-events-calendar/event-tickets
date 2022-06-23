<?php
/**
 * Handles registering Providers for the TEC\Events_Community\Custom_Tables\V1 (RBE) namespace.
 *
 * @since   TBD
 *
 * @package TEC\Events_Community\Custom_Tables\V1;
 */

namespace TEC\Tickets\Custom_Tables\V1;

use tad_DI52_ServiceProvider;
use TEC\Events\Custom_Tables\V1\Migration\State;

/**
 * Class Provider.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Custom_Tables\V1;
 */
class Provider extends tad_DI52_ServiceProvider {
	/**
	 * @var bool
	 */
	protected $has_registered = false;

	/**
	 * Registers any dependent providers.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the Event-wide maintenance mode was activated or not.
	 */
	public function register() {
		if ( $this->has_registered ) {

			return false;
		}

		if ( ! defined( 'TEC_ET_CUSTOM_TABLES_V1_ROOT' ) ) {
			define( 'TEC_ET_CUSTOM_TABLES_V1_ROOT', __DIR__ );
		}
		$state = $this->container->make( State::class );
		if ( $state->should_lock_for_maintenance() ) {
			$this->container->register( Migration\Maintenance_Mode\Provider::class );
		}
		$this->has_registered = true;

		return true;
	}
}