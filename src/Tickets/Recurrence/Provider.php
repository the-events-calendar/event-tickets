<?php

namespace TEC\Tickets\Recurrence;

use TEC\Events\Custom_Tables\V1\Migration\State as Custom_Tables_Migration;

class Provider extends \tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {

		if ( ! class_exists( Custom_Tables_Migration::class ) || ! tribe( Custom_Tables_Migration::class )->is_migrated() ) {
			return;
		}

		$this->container->register( Compatibility::class );

		$this->register_hooks();
	}

	public function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
	}
}