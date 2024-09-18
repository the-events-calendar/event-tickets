<?php
/**
 * Event Tickets Order Modifiers Provider.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers
 */

declare( strict_types=1 );

namespace TEC\Tickets\Order_Modifiers;

use TEC\Common\lucatume\DI52\ServiceProvider;

/**
 * Class Provider
 *
 * @since TBD
 */
class Provider extends ServiceProvider {

	/**
	 * Registers the service provider bindings.
	 *
	 * @return void The method does not return any value.
	 */
	public function register() {
		$this->container->singleton( static::class, $this );

		/**
		 * Fires when the provider is registered.
		 *
		 * @since TBD
		 *
		 * @param Provider $this The provider instance.
		 */
		do_action( 'tec_tickets_order_modifiers_register', $this );

		// Register the custom table classes.
		$this->container->register( Controller::class );
	}
}
