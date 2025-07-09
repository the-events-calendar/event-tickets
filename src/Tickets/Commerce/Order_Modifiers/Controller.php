<?php
/**
 * Event Tickets Order Modifiers Controller.
 *
 * @since 5.18.0
 * @package TEC\Tickets\Commerce\Order_Modifiers
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers;

use InvalidArgumentException;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Config;
use TEC\Tickets\Commerce\Flag_Actions\Flag_Action_Handler;
use TEC\Tickets\Commerce\Flag_Actions\Flag_Action_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\API\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Admin\Editor;
use TEC\Tickets\Commerce\Order_Modifiers\Admin\Order_Modifier_Fee_Metabox;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Coupons as Coupons_Checkout;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Fees as Agnostic_Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Coupons as PayPal_Checkout_Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\PayPal\Fees as Paypal_Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe\Fees as Stripe_Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe\Coupons as Stripe_Checkout_Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Flag_Actions\Decrease_Coupon_Usage;
use TEC\Tickets\Commerce\Order_Modifiers\Flag_Actions\Increase_Coupon_Usage;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Coupon_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Table_Views\Fee_Table;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Valid_Types;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Percent_Value;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Utils\Currency;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Main Order Modifiers Controller.
 *
 * @since 5.18.0
 */
final class Controller extends Controller_Contract {

	use Valid_Types;

	/**
	 * The flag action classes.
	 *
	 * @var Flag_Action_Interface[]
	 */
	protected array $flag_action_classes = [
		Decrease_Coupon_Usage::class,
		Increase_Coupon_Usage::class,
	];

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since 5.18.0
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		$this->container->get( Tables::class )->unregister();
		$this->container->get( Paypal_Checkout_Fees::class )->unregister();
		$this->container->get( Stripe_Checkout_Fees::class )->unregister();
		$this->container->get( Agnostic_Checkout_Fees::class )->unregister();
		$this->container->get( Editor::class )->unregister();
		$this->container->get( Coupons_Checkout::class )->unregister();
		$this->container->get( Stripe_Checkout_Coupons::class )->unregister();
		$this->container->get( PayPal_Checkout_Coupons::class )->unregister();

		// API classes.
		$this->container->get( Coupons::class )->unregister();
		$this->container->get( Fees::class )->unregister();

		if ( is_admin() ) {
			$this->container->get( Modifier_Admin_Handler::class )->unregister();
			$this->container->get( Order_Modifier_Fee_Metabox::class )->unregister();
		}

		remove_action( 'init', [ $this, 'set_currency_defaults' ] );
		remove_action( 'init', [ $this, 'run_deprecated_coupon_filter' ] );
	}

	/**
	 * Registers the service provider bindings.
	 *
	 * @since 5.18.0
	 *
	 * @return void The method does not return any value.
	 */
	public function do_register(): void {
		// Add the group path for the order-modifiers assets.
		Config::add_group_path( 'et-order-modifiers', Tickets_Plugin::instance()->plugin_path . 'build/', 'OrderModifiers/' );

		$this->container->register( Tables::class );
		$this->container->register( Paypal_Checkout_Fees::class );
		$this->container->register( Stripe_Checkout_Fees::class );
		$this->container->register( Agnostic_Checkout_Fees::class );
		$this->container->register( Editor::class );
		$this->container->register( Coupons_Checkout::class );
		$this->container->register( Stripe_Checkout_Coupons::class );
		$this->container->register( PayPal_Checkout_Coupons::class );

		// API classes.
		$this->container->register( Coupons::class );
		$this->container->register( Fees::class );

		if ( is_admin() ) {
			$this->container->register( Modifier_Admin_Handler::class );
			$this->container->register( Order_Modifier_Fee_Metabox::class );
		}

		$this->container->singleton( Fee::class );
		$this->container->singleton( Fee_Table::class );
		$this->container->singleton( Coupon_Table::class );
		$this->container->singleton( Coupon::class );

		$this->register_flag_actions();

		add_action( 'init', [ $this, 'run_deprecated_coupon_filter' ] );
		add_action( 'init', [ $this, 'set_currency_defaults' ] );
	}

	/**
	 * Set up currency default values in the value classes.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	public function set_currency_defaults() {
		$currency_code       = Currency::get_currency_code();
		$thousands_separator = Currency::get_currency_separator_thousands( $currency_code );
		$decimal_separator   = Currency::get_currency_separator_decimal( $currency_code );

		Currency_Value::set_defaults(
			Currency::get_currency_symbol( $currency_code ),
			$thousands_separator,
			$decimal_separator,
			Currency::get_currency_symbol_position( $currency_code )
		);

		Percent_Value::set_defaults(
			$thousands_separator,
			$decimal_separator
		);

		Precision_Value::set_default_precision( (int) Currency::get_currency_precision( $currency_code ) );
	}

	/**
	 * Register the flag actions.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	protected function register_flag_actions() {
		/** @var Flag_Action_Handler $handler */
		$handler = $this->container->get( Flag_Action_Handler::class );

		foreach ( $this->flag_action_classes as $class ) {
			$instance = new $class();
			$this->container->singleton( $class, $instance );
			$handler->register_flag_action( $instance );
		}
	}

	/**
	 * Magic method to handle dynamic method calls.
	 *
	 * @since 5.21.0
	 *
	 * @param string $name      The method name.
	 * @param array  $arguments The method arguments.
	 *
	 * @return mixed The method return value.
	 * @throws InvalidArgumentException If the method does not exist.
	 */
	public function __call( $name, $arguments ) {
		$method = __CLASS__ . "::{$name}";
		switch ( $name ) {
			case 'filter_out_coupons':
				_deprecated_function( esc_html( $method ), '5.21.0', 'No replacement available.' );

				return $arguments[0] ?? [];

			default:
				throw new InvalidArgumentException( sprintf( 'Method %s does not exist.', esc_html( $method ) ) );
		}
	}

	/**
	 * Run the deprecated coupon filter.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	public function run_deprecated_coupon_filter() {
		/**
		 * Filters whether the coupons are enabled.
		 *
		 * This filter will be removed when the Coupon functionality is ready for production.
		 *
		 * @since 5.18.0
		 * @deprecated 5.21.0
		 *
		 * @param bool $enabled Whether the coupons are enabled.
		 */
		apply_filters_deprecated(
			'tec_tickets_commerce_order_modifiers_coupons_enabled',
			[ false ],
			'5.21.0',
			'',
			'The coupon functionality is now included for all sites.'
		);
	}

	/**
	 * Get a specific modifier strategy.
	 *
	 * Retrieves the appropriate strategy class based on the provided modifier type.
	 * The strategy class must implement the Modifier_Strategy_Interface interface.
	 *
	 * If the class is not found or does not implement the required interface, an exception will be thrown.
	 *
	 * @since 5.18.0
	 *
	 * @param string $modifier The modifier type to retrieve (e.g., 'coupon', 'fee').
	 *
	 * @return Modifier_Strategy_Interface The strategy class if found.
	 * @throws InvalidArgumentException If the modifier strategy class is not found or does not implement Modifier_Strategy_Interface.
	 */
	public function get_modifier( string $modifier ): Modifier_Strategy_Interface {
		// Sanitize the modifier parameter to ensure it's a valid string.
		$modifier = sanitize_key( $modifier );

		$modifiers = self::get_modifier_types();

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
	 * Get the display name for a specific modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param string $modifier The slug of the modifier (e.g., 'coupon', 'fee').
	 *
	 * @return string|null The display name of the modifier or null if not found.
	 */
	public static function get_modifier_display_name( string $modifier ): ?string {
		$modifiers = self::get_modifier_types();

		// Return the display name if the modifier exists in the array.
		if ( isset( $modifiers[ $modifier ]['display_name'] ) ) {
			return $modifiers[ $modifier ]['display_name'];
		}

		return null;
	}
}
