<?php
/**
 * Tickets Commerce: Free Gateway Provider.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */

namespace TEC\Tickets\Commerce\Gateways\Free;

use TEC\Common\Contracts\Service_Provider;

/**
 * Service provider for the Tickets Commerce: Free Gateway.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Free
 */
class Provider extends Service_Provider {

	/**
	 * Register the provider singletons.
	 *
	 * @since 5.10.0
	 */
	public function register() {
		$this->container->singleton( Gateway::class );
		$this->container->singleton( Order::class );
		$this->container->singleton( REST\Order_Endpoint::class );

		$this->register_assets();
		$this->register_hooks();
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider
	 *
	 * @since 5.10.0
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.10.0
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		$this->container->singleton( Hooks::class, $hooks );
	}
}
