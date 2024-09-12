<?php
/**
 *
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
	 * @inheritDoc
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
	}
}
