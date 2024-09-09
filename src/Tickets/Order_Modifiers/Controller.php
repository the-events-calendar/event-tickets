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
	 * Cached list of available modifiers.
	 *
	 * @since TBD
	 *
	 * @var array|null
	 */
	protected static ?array $cached_modifiers = null;

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
	 * Acts as a whitelist for potential Modifiers.
	 *
	 * @since TBD
	 *
	 * @return array List of registered modifier strategies.
	 */
	public static function get_modifiers(): array {
		// Check if the modifiers are cached.
		if ( null !== self::$cached_modifiers ) {
			return self::$cached_modifiers;
		}

		// Default modifiers with display name, slug, and class.
		$modifiers = [
			'coupon' => [
				'display_name' => __( 'Coupons', 'event-tickets' ),
				'slug'         => 'coupon',
				'class'        => Coupon::class,
			],
			'fee'    => [
				'display_name' => __( 'Booking Fees', 'event-tickets' ),
				'slug'         => 'fee',
				'class'        => Booking_Fee::class,
			],
		];

		/**
		 * Filters the list of available modifiers for Order Modifiers.
		 *
		 * This allows developers to add or modify the default list of order modifiers.
		 *
		 * @since TBD
		 *
		 * @param array $modifiers An array of default modifiers, each containing 'display_name', 'slug', and 'class'.
		 */
		$modifiers = apply_filters( 'tec_tickets_order_modifiers', $modifiers );

		// Validate modifiers after the filter.
		foreach ( $modifiers as $key => $modifier ) {
			if ( ! isset( $modifier['class'], $modifier['slug'], $modifier['display_name'] ) || ! class_exists( $modifier['class'], false ) ) {
				unset( $modifiers[ $key ] ); // Remove invalid modifiers.
				continue;
			}
		}

		// Cache the result to avoid recomputation.
		self::$cached_modifiers = $modifiers;

		return $modifiers;
	}

	/**
	 * Get a specific modifier strategy.
	 *
	 * @since TBD
	 *
	 * @param string $modifier The modifier type to retrieve (e.g., 'coupon', 'fee').
	 *
	 * @return Modifier_Strategy_Interface|null The strategy class or null if not found.
	 */
	public function get_modifier( string $modifier ): ?Modifier_Strategy_Interface {
		// Sanitize the modifier parameter to ensure it's a valid string.
		$modifier = sanitize_key( $modifier );

		$modifiers = self::get_modifiers();

		// Ensure the requested modifier exists in the whitelist and class is valid.
		if ( isset( $modifiers[ $modifier ] ) && is_subclass_of( $modifiers[ $modifier ]['class'], Modifier_Strategy_Interface::class ) ) {
			// Instantiate and return the strategy class.
			$strategy_class = $modifiers[ $modifier ]['class'];
			return new $strategy_class();
		}

		return null; // Return null if the modifier is not found or invalid.
	}
}
