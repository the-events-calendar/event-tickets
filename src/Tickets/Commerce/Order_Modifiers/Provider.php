<?php
/**
 * Event Tickets Order Modifiers Provider.
 *
 * @since   TBD
 * @package TEC\Tickets\Commerce\Order_Modifiers
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers;

use TEC\Common\lucatume\DI52\ServiceProvider;
use TEC\Tickets\Commerce\Order_Modifiers\Admin\Order_Modifier_Fee_Metabox;
use TEC\Tickets\Commerce\Order_Modifiers\API\Localization;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Paypal\Fees as Paypal_Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe\Fees as Stripe_Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Checkout\Fees as Agnostic_Checkout_Fees;
use TEC\Tickets\Commerce\Order_Modifiers\API\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\API\Fees;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Registerable;

/**
 * Class Provider
 *
 * @since TBD
 */
final class Provider extends ServiceProvider {

	/**
	 * The classes to register with a tag.
	 *
	 * @var array
	 */
	protected array $tagged_classes = [];

	/**
	 * Registers the service provider bindings.
	 *
	 * @return void The method does not return any value.
	 */
	public function register() {
		$this->container->singleton( self::class, $this );

		/**
		 * Fires when the provider is registered.
		 *
		 * @since TBD
		 *
		 * @param Provider $this The provider instance.
		 */
		do_action( 'tec_tickets_commerce_order_modifiers_register', $this );

		// Register the common classes.
		$this->register_common_classes();

		// Register the Fee classes.
		$this->register_fee_classes();

		/**
		 * Filters whether the coupons are enabled.
		 *
		 * This filter will be removed when the Coupon functionality is ready for production.
		 *
		 * @since TBD
		 *
		 * @param bool $enabled Whether the coupons are enabled.
		 */
		if ( apply_filters( 'tec_tickets_commerce_order_modifiers_coupons_enabled', false ) ) {
			$this->register_couopon_classes();
		} else {
			$this->filter_out_coupons();
		}

		// Tag our classes that have their own registration needs.
		$this->container->tag( $this->tagged_classes, 'order_modifiers' );

		foreach ( $this->container->tagged( 'order_modifiers' ) as $class_instance ) {
			if ( $class_instance instanceof Registerable || method_exists( $class_instance, 'register' ) ) {
				$class_instance->register();
			}
		}
	}

	/**
	 * Register the common classes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_common_classes(): void {
		// Register the custom table controller.
		$this->container->register( Controller::class );
		$this->container->bind( Editor_Config::class, fn() => new Editor_Config() );

		// Add to the tag class array.
		$this->tagged_classes = array_merge(
			$this->tagged_classes,
			[
				Editor_Config::class,
				Localization::class,
				Modifier_Admin_Handler::class,
			]
		);
	}

	/**
	 * Register the Fee classes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_fee_classes(): void {
		$this->container->singleton( Fee::class );
		$this->container->bind( Fees::class, fn() => new Fees() );

		// Add to the tag class array.
		$this->tagged_classes = array_merge(
			$this->tagged_classes,
			[
				Order_Modifier_Fee_Metabox::class,
				Paypal_Checkout_Fees::class,
				Stripe_Checkout_Fees::class,
				Agnostic_Checkout_Fees::class,
				Fees::class,
			]
		);
	}

	/**
	 * Register the Coupon classes.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_couopon_classes(): void {
		$this->container->singleton( Coupon::class );
		$this->container->bind( Coupons::class, fn() => new Coupons() );

		// Add to the tag class array.
		$this->tagged_classes = array_merge(
			$this->tagged_classes,
			[
				Coupons::class,
			]
		);
	}

	/**
	 * Filter out the coupons.
	 *
	 * This will be removed when the Coupon functionality is ready for production.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function filter_out_coupons() {
		$remove_coupon_array_key = function ( array $items ): array {
			unset( $items['coupon'] );

			return $items;
		};

		add_filter( 'tec_tickets_commerce_order_modifiers', $remove_coupon_array_key );
		add_filter( 'tec_tickets_commerce_order_modifier_types', $remove_coupon_array_key );
	}
}
