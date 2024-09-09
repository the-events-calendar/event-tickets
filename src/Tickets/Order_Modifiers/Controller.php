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
use TEC\Tickets\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;

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
		$this->container->singleton( Coupon::class );
		$this->hook();
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
		add_action( 'admin_menu', tribe_callback( Modifier_Settings::class, 'add_tec_tickets_order_modifiers_page' ), 15 );
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

	/**
	 * Get the list of available modifiers.
	 *
	 * @since TBD
	 *
	 * @return array List of registered modifier strategies.
	 */
	public function get_modifiers(): array {
		// @todo redscar - Should this be moved to a class param?
		// Default modifiers.
		$modifiers = [
			'coupon' => Coupon::class,
			//'fees'   => Booking_Fee::class,
		];

		/**
		 * Filter to allow new modifiers to be added.
		 * Developers can hook into this to register new modifiers.
		 *
		 * @since TBD
		 */
		return apply_filters( 'tec_tickets_order_modifiers', $modifiers );
	}

	/**
	 * Get a specific modifier strategy.
	 *
	 * @since TBD
	 *
	 * @param string $modifier The modifier type to retrieve (e.g., 'coupon', 'fees').
	 *
	 * @return Modifier_Strategy_Interface|null The strategy class or null if not found.
	 */
	public function get_modifier( string $modifier ): ?Modifier_Strategy_Interface {
		$modifiers = $this->get_modifiers();

		if ( isset( $modifiers[ $modifier ] ) ) {
			// @todo redscar - Need to add more validation logic.
			// Instantiate the correct strategy class.
			return new $modifiers[ $modifier ]();
		}

		return null; // Return null if the modifier does not exist.
	}
}
