<?php
/**
 * Stripe Coupons controller.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe;

use Closure;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Utils\Value;
use Tribe__Assets;
use WP_Post;

/**
 * Class Coupons
 *
 * @since 5.21.0
 */
class Coupons extends Controller_Contract {

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 5.21.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			[ $this, 'add_meta_data_to_stripe' ],
			10,
			2
		);

		add_action(
			'tec-tickets-commerce-checkout-shortcode-assets',
			[ $this, 'adjust_stripe_asset_enqueue_condition' ],
			1
		);
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since 5.21.0
	 *
	 * @return void Filters and actions hooks added by the controller are be removed.
	 */
	public function unregister(): void {
		remove_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			[ $this, 'add_meta_data_to_stripe' ]
		);

		remove_action(
			'tec-tickets-commerce-checkout-shortcode-assets',
			[ $this, 'adjust_stripe_asset_enqueue_condition' ],
			1
		);
	}

	/**
	 * Add coupon metadata to the Stripe payment intent.
	 *
	 * @since 5.21.0
	 *
	 * @param array   $metadata The metadata to be passed to Stripe.
	 * @param WP_Post $order    The order object.
	 *
	 * @return array The updated metadata.
	 */
	public function add_meta_data_to_stripe( array $metadata, WP_Post $order ) {
		// Skip adding metadata if the order has no coupons.
		if ( empty( $order->coupons ) ) {
			return $metadata;
		}

		// Loop through the coupon items and format each one as "Coupon Slug (quantity): Subtotal".
		$coupon_metadata = [];
		foreach ( $order->coupons as $coupon ) {
			// Skip the coupon if it lacks required data or has an invalid price.
			if ( ! isset( $coupon['slug'], $coupon['sub_total'] ) ) {
				continue;
			}

			/** @var Value $sub_total */
			$sub_total = $coupon['sub_total'];

			$coupon_metadata[] = sprintf(
				'%s: %s',
				$coupon['slug'],
				$sub_total->get_decimal()
			);
		}

		if ( ! empty( $coupon_metadata ) ) {
			$metadata['coupons'] = implode( ', ', $coupon_metadata );
		}

		return $metadata;
	}

	/**
	 * Adjusts the condition for enqueuing the Stripe checkout asset.
	 *
	 * This is necessary because the normal logic for enqueueing the asset is based (in part)
	 * on the value of the cart. If the cart total is zero, the asset is not enqueued. However,
	 * we still want to enqueue the asset if a coupon is present, even if the cart total is zero.
	 * This is because the customer could need to remove the coupon, and we still rely on the
	 * Payment Intent data from the Stripe checkout asset for that functionality.
	 *
	 * @see \TEC\Tickets\Commerce\Gateways\Stripe\Assets::should_enqueue_assets()
	 * @since 5.21.0
	 *
	 * @return void
	 */
	public function adjust_stripe_asset_enqueue_condition() {
		// Only change the enqueue condition if the cart has a coupon present.
		$cart = tribe( Cart::class )->get_repository();
		if ( empty( $cart->get_items_in_cart( false, 'coupon' ) ) ) {
			return;
		}

		/** @var Asset $asset */
		$asset = Tribe__Assets::instance()->get( 'tec-tickets-commerce-gateway-stripe-checkout' );

		// If for some reason we didn't get an asset back, we can't do anything.
		if ( ! $asset instanceof Asset ) {
			return;
		}

		// If the original condition would return true, we don't need to change it.
		$original_condition = $asset->get_condition();
		if ( is_callable( $original_condition ) && $original_condition() ) {
			return;
		}

		// Set the new condition for enqueuing the asset.
		$asset->set_condition(
			function (): bool {
				// If it's not the checkout page, we don't need to enqueue the asset.
				if ( ! tribe( Checkout::class )->is_current_page() ) {
					return false;
				}

				// If the gateway is not enabled, we don't need to enqueue the asset.
				if ( ! tribe_is_truthy( tribe_get_option( Gateway::get_enabled_option_key() ) ) ) {
					return false;
				}

				/** @var Gateway $gateway */
				$gateway = tribe( Gateway::class );

				// Define our own "is_active()" method to avoid calling should_show().
				$is_active = Closure::bind(
					function () {
						return tribe( static::$merchant )->is_active();
					},
					$gateway,
					$gateway
				);

				if ( ! $is_active() ) {
					return false;
				}

				return true;
			}
		);
	}
}
