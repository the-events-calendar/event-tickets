<?php
/**
 * Handles the registration of all the Order Modifiers managed by the plugin.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers;
 */

namespace TEC\Tickets\Order_Modifiers;

use TEC\Common\StellarWP\Schema\Register as Schema_Register;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers;
 */
class Controller extends \TEC\Common\Contracts\Provider\Controller {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		// Register classes here.
		add_action( 'tribe_plugins_loaded', [ $this, 'register_tables' ] );
	}

	/**
	 * Any hooking any class needs happen here.
	 *
	 * In place of delegating the hooking responsibility to the single classes they are all hooked here.
	 *
	 * @since TBD
	 */
	protected function hook() {
		// Hooks here.
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_plugins_loaded', [ $this, 'register_tables' ] );
	}

	/**
	 * Registers the custom tables and makes them available in the container as singletons.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function register_tables(): void {
		$this->container->singleton( Order_Modifiers::class, Schema_Register::table( Order_Modifiers::class ) );
		$this->container->singleton( Order_Modifiers_Meta::class, Schema_Register::table( Order_Modifiers_Meta::class ) );
	}
}
