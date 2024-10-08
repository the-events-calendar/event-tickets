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
use TEC\Tickets\Order_Modifiers\Admin\Order_Modifier_Fee_Metabox;
use TEC\Tickets\Order_Modifiers\Checkout\Coupons as Coupon_Checkout;
use TEC\Tickets\Order_Modifiers\Modifiers\Fee;
use TEC\Tickets\Order_Modifiers\Checkout\Gateway\Paypal\Fees as Paypal_Checkout_Fees;
use TEC\Tickets\Order_Modifiers\Checkout\Gateway\Stripe\Fees as Stripe_Checkout_Fees;

/**
 * Class Provider
 *
 * @since TBD
 */
final class Provider extends ServiceProvider {

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
		do_action( 'tec_tickets_order_modifiers_register', $this );

		// Register the custom table controller.
		$this->container->register( Controller::class );

		// Register the table views.
		$this->container->singleton( Coupon::class );
		$this->container->singleton( Fee::class );

		// Tag our classes that have their own registration needs.
		$this->container->tag(
			[
				Modifier_Admin_Handler::class,
				Order_Modifier_Fee_Metabox::class,
				Paypal_Checkout_Fees::class,
				Stripe_Checkout_Fees::class,
				Coupon_Checkout::class,
			],
			'order_modifiers'
		);

		foreach ( $this->container->tagged( 'order_modifiers' ) as $class_instance ) {
			$class_instance->register();
		}
	}
}
