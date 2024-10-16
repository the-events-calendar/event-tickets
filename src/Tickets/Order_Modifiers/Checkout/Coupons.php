<?php

namespace TEC\Tickets\Order_Modifiers\Checkout;

use TEC\Common\StellarWP\Assets\Asset;
use TEC\Tickets\Commerce\Gateways\Stripe\Gateway;
use TEC\Tickets\Commerce\Gateways\Stripe\Payment_Intent;
use TEC\Tickets\Commerce\Order;
use TEC\Tickets\Commerce\Utils\Value;
use TEC\Tickets\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Registerable;
use Tribe__Assets;

class Coupons implements Registerable {

	protected Coupon $coupon;

	public function __construct() {
		$this->coupon = new Coupon();

	}

	/**
	 * Registers hooks and AJAX actions.
	 *
	 * @since TBD
	 */
	public function register(): void {
		// Hook for displaying coupons in the checkout.
		add_action(
			'tec_tickets_commerce_checkout_cart_before_footer_quantity',
			[
				$this,
				'display_coupon_section',
			],
			40,
			3
		);

		// Register AJAX handlers (for both logged-in users and guests).
		add_action( 'wp_ajax_validate_coupon', [ $this, 'validate_coupon_ajax' ] );
		add_action( 'wp_ajax_nopriv_validate_coupon', [ $this, 'validate_coupon_ajax' ] );

		// Add asset localization to ensure the script has the necessary data.
		add_action( 'init', fn() => $this->localize_assets() );
	}

	/**
	 * Displays the coupon section in the checkout.
	 *
	 * @since TBD
	 *
	 * @param array            $items The items in the cart.
	 * @param \Tribe__Template $template The template object for rendering.
	 *
	 * @param \WP_Post         $post The current post object.
	 */
	public function display_coupon_section( \WP_Post $post, array $items, \Tribe__Template $template ): void {
		// Display the coupon section template.
		$template->template(
			'checkout/order-modifiers/Coupons',
			[
				// Additional data if needed.
			]
		);
	}

	/**
	 * Handles the AJAX request to validate or remove a coupon.
	 *
	 * This method validates or removes a coupon and updates the payment intent accordingly.
	 *
	 * @since TBD
	 */
	public function validate_coupon_ajax(): void {
		// Early bail: Check if the nonce is valid.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_rest' ) ) {
			wp_send_json_error( [ 'message' => 'Invalid nonce. Please refresh the page and try again.' ] );
			return;
		}

		// Early bail: Check if coupon or remove_coupon is provided.
		$is_removing_coupon = ! empty( $_POST['remove_coupon'] );
		if ( empty( $_POST['coupon'] ) && ! $is_removing_coupon ) {
			wp_send_json_error( [ 'message' => __( 'No coupon provided.', 'event-tickets' ) ] );
			return;
		}

		// Sanitize the coupon code.
		$coupon_code = sanitize_text_field( $_POST['coupon'] ?? '' );

		// Early bail: Extract the paymentIntent ID, or exit if not provided.
		if ( ! isset( $_POST['data']['paymentIntentData']['id'] ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing Payment Intent ID.', 'event-tickets' ) ] );
			return;
		}

		$payment_intent_id = sanitize_text_field( $_POST['data']['paymentIntentData']['id'] );

		// Early bail: Get the purchaser data.
		$purchaser_data = tribe( Order::class )->get_purchaser_data( $_POST['data'] );
		if ( empty( $purchaser_data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid purchaser data.', 'event-tickets' ) ] );
			return;
		}

		// Get the current order data.
		$order = tribe( Order::class )->create_from_cart( tribe( Gateway::class ), $purchaser_data );
		if ( empty( $order ) ) {
			wp_send_json_error( [ 'message' => __( 'Failed to retrieve order data.', 'event-tickets' ) ] );
			return;
		}

		// Retrieve the current total value of the order in integer form.
		$original_order_value = $order->total_value->get_integer();

		// Handle coupon removal.
		if ( $is_removing_coupon ) {
			// If removing a coupon, set the original order value.
			$body['amount'] = $original_order_value;
			Payment_Intent::update( $payment_intent_id, $body );

			wp_send_json_success( [
				'message' => __( 'Coupon removed successfully.', 'event-tickets' ),
				'amount'  => $order->total_value->get_currency(),
			] );
			return;
		}

		// Assume a hardcoded coupon discount of 100 for now.
		$coupon_discount = 100;

		// Ensure that the discount doesn't exceed the total order value.
		$new_order_value = max( 0, $original_order_value - $coupon_discount );

		// Update the payment intent with the new value.
		$body['amount'] = $new_order_value;
		Payment_Intent::update( $payment_intent_id, $body );

		$currency_code = $order->currency;
		// Construct a response with success and the discount applied.
		$response = [
			'valid'    => true,
			'discount' => Value::create( $this->coupon->convert_from_raw_amount( $coupon_discount ) )->get_currency(),
			'message'  => sprintf( __( 'Coupon "%s" applied successfully.', 'event-tickets' ), $coupon_code ),
			'amount'   => Value::create( $this->coupon->convert_from_raw_amount( $new_order_value ) )->get_currency(),
		];



		// Send the success response back to the client.
		wp_send_json_success( $response );
	}

	/**
	 * Localizes the assets for the coupon section.
	 *
	 * @return void
	 */
	protected function localize_assets() {
		/** @var Asset $main */
		$main = Tribe__Assets::instance()->get( 'tribe-tickets-commerce-js' );
		$main->add_localize_script(
			'tecTicketsCommerce',
			[
				'restUrl' => tribe_tickets_rest_url(),
			]
		);
	}
}
