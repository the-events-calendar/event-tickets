<?php
/**
 * Handles the registration of all the Order Modifiers managed by the plugin.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers;
 */

namespace TEC\Tickets\Order_Modifiers;

use InvalidArgumentException;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Schema\Register as Schema_Register;
use TEC\Common\StellarWP\Schema\Config as Schema_Config;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
use TEC\Tickets\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Order_Modifiers;
 */
class Controller extends Controller_Contract {

	/**
	 * Cached list of available modifiers.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected static array $cached_modifiers = [];

	/**
	 * The callback to register the tables.
	 *
	 * @since TBD
	 *
	 * @var callable
	 */
	protected $register_callback;

	/**
	 * List of custom tables to register.
	 *
	 * @var Table[]
	 */
	protected $custom_tables = [
		Order_Modifiers::class,
		Order_Modifiers_Meta::class,
		Order_Modifier_Relationships::class,
	];

	/**
	 * ServiceProvider constructor.
	 *
	 * @since TBD
	 *
	 * @param Container $container The DI container instance.
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );

		// Set up the callback function to register the tables.
		$this->register_callback = function () {
			$this->register_tables();
		};
	}

	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		Schema_Config::set_container( $this->container );
		Schema_Config::set_db( DB::class );

		add_action( 'tribe_plugins_loaded', $this->register_callback );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_plugins_loaded', $this->register_callback );
	}

	/**
	 * Registers the custom tables and makes them available in the container as singletons.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_tables(): void {
		foreach ( $this->custom_tables as $table ) {
			$this->container->singleton( $table, Schema_Register::table( $table ) );
		}
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
		// If cached modifiers exist, return them.
		if ( ! empty( self::$cached_modifiers ) ) {
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
				'display_name' => __( 'Fees', 'event-tickets' ),
				'slug'         => 'fee',
				'class'        => Fee::class,
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
			if ( ! isset( $modifier['class'], $modifier['slug'], $modifier['display_name'] ) || ! class_exists( $modifier['class'], true ) ) {
				unset( $modifiers[ $key ] ); // Remove invalid modifiers.
			}
		}

		// Cache the result.
		self::$cached_modifiers = $modifiers;

		return $modifiers;
	}

	/**
	 * Clear the cached list of order modifiers.
	 *
	 * This method is useful when plugins or settings that affect the order modifiers change.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public static function clear_cached_modifiers(): void {
		self::$cached_modifiers = [];
	}

	/**
	 * Get a specific modifier strategy.
	 *
	 * Retrieves the appropriate strategy class based on the provided modifier type.
	 * The strategy class must implement the Modifier_Strategy_Interface interface.
	 *
	 * If the class is not found or does not implement the required interface, an exception will be thrown.
	 *
	 * @since TBD
	 *
	 * @param string $modifier The modifier type to retrieve (e.g., 'coupon', 'fee').
	 *
	 * @return Modifier_Strategy_Interface The strategy class if found.
	 * @throws InvalidArgumentException If the modifier strategy class is not found or does not implement Modifier_Strategy_Interface.
	 */
	public function get_modifier( string $modifier ): Modifier_Strategy_Interface {
		// Sanitize the modifier parameter to ensure it's a valid string.
		$modifier = sanitize_key( $modifier );

		$modifiers = self::get_modifiers();

		// Ensure the requested modifier exists in the whitelist and the class implements the correct interface.
		if ( isset( $modifiers[ $modifier ] ) && is_subclass_of( $modifiers[ $modifier ]['class'], Modifier_Strategy_Interface::class ) ) {
			// Instantiate and return the strategy class.
			$strategy_class = $modifiers[ $modifier ]['class'];
			return new $strategy_class();
		}

		// Throw an exception if the modifier class is not found or does not implement the required interface.
		throw new InvalidArgumentException( sprintf( 'Modifier strategy class for "%s" not found or does not implement Modifier_Strategy_Interface.', $modifier ) );
	}

	/**
	 * Drop all custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of tables dropped.
	 */
	public function drop_tables(): int {
		return $this->table_helper( 'drop' );
	}

	/**
	 * Truncate all custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of tables truncated.
	 */
	public function truncate_tables(): int {
		return $this->table_helper( 'truncate' );
	}

	/**
	 * Helper method to drop or truncate custom tables.
	 *
	 * @since TBD
	 *
	 * @param string $action The action to perform on the tables. Either 'drop' or 'truncate'.
	 *
	 * @return int The number of tables affected.
	 * @throws InvalidArgumentException If an invalid action is provided.
	 */
	protected function table_helper( string $action ): int {
		switch ( $action ) {
			case 'drop':
				$query = 'DROP TABLE IF EXISTS `%s`';
				break;
			case 'truncate':
				$query = 'TRUNCATE TABLE `%s`';
				break;
			default:
				throw new InvalidArgumentException( 'Invalid action provided.' );
		}

		DB::query( 'SET FOREIGN_KEY_CHECKS = 0' );

		$affected = 0;
		foreach ( $this->custom_tables as $table ) {
			$affected += DB::query(
				sprintf(
					$query,
					esc_sql( $table::table_name() )
				)
			);
		}

		DB::query( 'SET FOREIGN_KEY_CHECKS = 1' );

		return $affected;
	}

	/**
	 * Get the display name for a specific modifier.
	 *
	 * @since TBD
	 *
	 * @param string $modifier The slug of the modifier (e.g., 'coupon', 'fee').
	 *
	 * @return string|null The display name of the modifier or null if not found.
	 */
	public static function get_modifier_display_name( string $modifier ): ?string {
		$modifiers = self::get_modifiers();

		// Return the display name if the modifier exists in the array.
		if ( isset( $modifiers[ $modifier ]['display_name'] ) ) {
			return $modifiers[ $modifier ]['display_name'];
		}

		return null; // Return null if the modifier slug doesn't exist.
	}
}
