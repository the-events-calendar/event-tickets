<?php
/**
 * Handles the registration of all the Order Modifiers managed by the plugin.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers;
 */

namespace TEC\Tickets\Commerce\Order_Modifiers;

use InvalidArgumentException;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Schema\Register as Schema_Register;
use TEC\Common\StellarWP\Schema\Config as Schema_Config;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifier_Relationships;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers;
use TEC\Tickets\Commerce\Order_Modifiers\Custom_Tables\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;

/**
 * Class Controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers;
 */
class Controller extends Controller_Contract {

	use Valid_Types;

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

		$modifiers = $this->get_modifiers();

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

		return null;
	}
}
