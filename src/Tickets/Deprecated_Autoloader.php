<?php
/**
 * Autoloader for deprecated classes.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets;

use TEC\Tickets\Commerce\Values\Base_Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Float_Value;
use TEC\Tickets\Commerce\Values\Integer_Value;
use TEC\Tickets\Commerce\Values\Legacy_Value_Factory;
use TEC\Tickets\Commerce\Values\Percent_Value;
use TEC\Tickets\Commerce\Values\Positive_Integer_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Values\Value_Interface;

/**
 * Class Deprecated_Autoloader
 *
 * @since TBD
 */
final class Deprecated_Autoloader {

	/**
	 * The class map for deprecated classes.
	 *
	 * The key is the deprecated class name and the value is the new class name.
	 * Note that the old class name should be fully qualified.
	 *
	 * @since TBD
	 *
	 * @var array<string, string>
	 */
	private array $class_map = [
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Base_Value::class             => Base_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Currency_Value::class         => Currency_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Float_Value::class            => Float_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Integer_Value::class          => Integer_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Legacy_Value_Factory::class   => Legacy_Value_Factory::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Percent_Value::class          => Percent_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Positive_Integer_Value::class => Positive_Integer_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Precision_Value::class        => Precision_Value::class,
		\TEC\Tickets\Commerce\Order_Modifiers\Values\Value_Interface::class        => Value_Interface::class,
	];

	/**
	 * The single instance of the class.
	 *
	 * @since TBD
	 *
	 * @var Deprecated_Autoloader
	 */
	private static $instance;

	/**
	 * Get the single instance of the class.
	 *
	 * @since TBD
	 *
	 * @return Deprecated_Autoloader
	 */
	public static function get_instance(): Deprecated_Autoloader {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Deprecated_Autoloader constructor.
	 *
	 * @since TBD
	 */
	private function __construct() {}

	/**
	 * Register the autoloader.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register() {
		spl_autoload_register( [ $this, 'load' ] );
	}

	/**
	 * Unregister the autoloader.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister() {
		spl_autoload_unregister( [ $this, 'load' ] );
	}

	/**
	 * Load the deprecated class.
	 *
	 * @since TBD
	 *
	 * @param string $class_name The class to load.
	 *
	 * @return void
	 */
	public function load( string $class_name ) {
		// Check if the class is in the class map, if not return.
		if ( ! array_key_exists( $class_name, $this->class_map ) ) {
			return;
		}

		// Log a notice that the class is deprecated.
		_deprecated_class(
			esc_html( $class_name ),
			'TBD',
			esc_html( $this->class_map[ $class_name ] )
		);

		// Alias the class to the new class.
		class_alias( $this->class_map[ $class_name ], $class_name );
	}
}
