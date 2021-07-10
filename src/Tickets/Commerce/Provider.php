<?php
/**
 * The main service provider for the Tickets Commerce.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use tad_DI52_ServiceProvider;
use TEC\Tickets\Commerce\Gateways;
use Tribe__Tickets__Main;

/**
 * Service provider for the Tickets Commerce.
 *
 * @since   5.1.6
 * @package TEC\Tickets\Commerce
 */
class Provider extends tad_DI52_ServiceProvider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.1.6
	 */
	public function register() {

		// Specifically prevents anything else from loading.
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return;
		}

		$this->register_hooks();
		$this->register_assets();

		$this->register_legacy_compat();

		// Register the SP on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets.commerce.provider', $this );

		// Register all singleton classes.
		$this->container->singleton( Gateways\Manager::class, Gateways\Manager::class );

		// Load any external SPs we might need.
		$this->container->register( Gateways\PayPal\Provider::class );
//		$this->container->register( Gateways\Legacy\Provider::class );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Commerce.
	 *
	 * @since 5.1.6
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for Tickets Commerce.
	 *
	 * @since 5.1.6
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets.hooks', $hooks );
	}

	/**
	 * Registers the provider handling compatibility with legacy payments from Tribe Tickets Commerce.
	 *
	 * @since 5.1.6
	 */
	protected function register_legacy_compat() {
		$v1_compat = new Legacy_Compat( $this->container );
		$v1_compat->register();

		$this->container->singleton( Legacy_Compat::class, $v1_compat );
		$this->container->singleton( 'tickets.legacy-compat', $v1_compat );
	}
}
