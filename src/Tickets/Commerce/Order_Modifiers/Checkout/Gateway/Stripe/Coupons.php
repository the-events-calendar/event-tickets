<?php
/**
 * Stripe Coupons controller.
 *
 * @since 5.21.0
 */

declare( strict_types=1 );

namespace TEC\Tickets\Commerce\Order_Modifiers\Checkout\Gateway\Stripe;

use Exception;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon;
use TEC\Tickets\Commerce\Utils\Value;
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
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'append_coupons_to_cart' ],
			10,
			2
		);

		add_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			[ $this, 'add_meta_data_to_stripe' ],
			10,
			2
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
			'tec_tickets_commerce_create_order_from_cart_items',
			[ $this, 'append_coupons_to_cart' ]
		);

		remove_filter(
			'tec_tickets_commerce_stripe_update_payment_intent_metadata',
			[ $this, 'add_meta_data_to_stripe' ]
		);
	}

	/**
	 * Appends coupons to the cart items when an order is made.
	 *
	 * @since 5.21.0
	 *
	 * @param array $items    The cart items.
	 * @param Value $subtotal The subtotal value.
	 *
	 * @return array The cart items with the coupons appended.
	 */
	public function append_coupons_to_cart( array $items, Value $subtotal ): array {
		// If we have no items, we have nothing to do.
		if ( empty( $items ) ) {
			return $items;
		}

		/** @var Cart $cart_page */
		$cart_page = tribe( Cart::class );
		$coupons   = $cart_page->get_items_in_cart( false, 'coupon' );

		// If we have no coupons, we have nothing to do.
		if ( empty( $coupons ) ) {
			return $items;
		}

		foreach ( $coupons as $id => $coupon_data ) {
			try {
				/** @var Coupon $coupon */
				$coupon = Coupon::find( (int) $coupon_data['coupon_id'] );
			} catch ( Exception $e ) {
				continue;
			}

			$items[ $id ] = [
				'id'           => $id,
				'type'         => 'coupon',
				'coupon_id'    => $coupon->id,
				'price'        => $coupon->raw_amount,
				'sub_total'    => $coupon->get_discount_amount( $subtotal->get_float() ),
				'display_name' => $coupon->display_name,
				'quantity'     => 1,
				'event_id'     => 0,
				'ticket_id'    => 0,
			];
		}

		return $items;
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
}
