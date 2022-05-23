<?php

namespace TEC\Tickets\Recurrence;

use tad_DI52_ServiceProvider;

class Provider extends tad_DI52_ServiceProvider {

	/**
	 * @inheritDoc
	 */
	public function register() {

		if ( ! class_exists( 'TEC\Events\Custom_Tables\V1\Provider' ) ) {
			return;
		}

		if ( ! \TEC\Events\Custom_Tables\V1\Provider::is_active() ) {
			return;
		}

		$this->container->singleton( Compatibility::class );

		$this->register_hooks();
	}

	public function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
	}
}