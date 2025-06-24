<?php
/**
 * Payment Handler for the Square gateway.
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */

namespace TEC\Tickets\Commerce\Gateways\Square;

use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Order;
use WP_Post;

/**
 * Class Payment Handler
 *
 * @since 5.24.0
 *
 * @package TEC\Tickets\Commerce\Gateways\Square
 */
class Payment_Handler {
	/**
	 * Store the Payment for the duration of the page load.
	 *
	 * @since 5.24.0
	 *
	 * @var array $payment The Square Payment.
	 */
	protected array $payment = [];

	/**
	 * Creates a payment for the current cart.
	 *
	 * @since 5.24.0
	 *
	 * @param string  $source_id       The source ID.
	 * @param WP_Post $order           The order post object.
	 * @param string  $square_order_id The Square order ID.
	 *
	 * @return array
	 */
	public function create_payment_for_order( string $source_id, WP_Post $order, string $square_order_id = '' ): array {
		// Somehow we already have a payment.
		if ( $this->get() ) {
			return $this->get();
		}

		// Let us look into the cookie.
		$payment = $this->get_existing_if_valid();
		if ( ! $payment ) {
			// If it all fails lets create a new one.
			$payment = Payment::create_from_order( $source_id, $order, $square_order_id );

			if ( isset( $payment['id'] ) && empty( $payment['errors'] ) ) {
				$this->store_payment_cookie( $payment['id'] );
			}
		}

		$this->set( $payment );

		return $payment;
	}

	/**
	 * Gets the existing Payment if it is valid.
	 *
	 * @since 5.24.0
	 *
	 * @return array|null
	 */
	protected function get_existing_if_valid(): ?array {
		$existing_payment_id = $this->get_payment_cookie();
		if ( ! $existing_payment_id ) {
			return null;
		}

		$payment = Payment::get( $existing_payment_id );
		if ( is_wp_error( $payment ) ) {
			return null;
		}

		if ( ! $payment ) {
			return null;
		}

		if ( ! empty( $payment['errors'] ) ) {
			return null;
		}

		return $payment;
	}

	/**
	 * Store the Payment for the duration of the page load.
	 *
	 * @since 5.24.0
	 *
	 * @param array $payment The Payment.
	 *
	 * @return void
	 */
	public function set( array $payment ): void {
		// Do some real basic validation.
		if ( empty( $payment['id'] ) ) {
			return;
		}

		$this->payment = $payment;

		// If the existing cookie is different, update it.
		if ( $this->get_payment_cookie() !== $payment['id'] ) {
			$this->store_payment_cookie( $payment['id'] );
		}
	}

	/**
	 * Gets the stored Payment.
	 *
	 * @since 5.24.0
	 *
	 * @return array
	 */
	public function get(): array {
		return $this->payment;
	}

	/**
	 * Where we store the payment ID in a cookie.
	 *
	 * @since 5.24.0
	 *
	 * @return string
	 */
	public function get_payment_cookie_name(): string {
		return Gateway::get_provider_key() . '-payment-' . tribe( Cart::class )->get_cart_hash();
	}

	/**
	 * Retrieve the payment ID from a cookie.
	 *
	 * @since 5.24.0
	 *
	 * @return ?string
	 */
	public function get_payment_cookie(): ?string {
		return ! empty( $_COOKIE[ $this->get_payment_cookie_name() ] ) ? sanitize_key( $_COOKIE[ $this->get_payment_cookie_name() ] ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
	}

	/**
	 * Store the payment ID in a cookie.
	 *
	 * @since 5.24.0
	 *
	 * @param mixed $payment_id The payment ID.
	 *
	 * @return bool
	 */
	public function store_payment_cookie( $payment_id ): bool {
		if ( headers_sent() ) {
			return false;
		}

		$expire = tribe( Cart::class )->get_cart_expiration();

		// When null means we are deleting.
		if ( null === $payment_id ) {
			$expire = 1;
		}

		/**
		 * Filter the cookie options for the payment cookie.
		 *
		 * @since 5.24.0
		 *
		 * @param array $cookie_options The cookie options.
		 *
		 * @return array
		 */
		$cookie_options = (array) apply_filters(
			'tec_tickets_commerce_square_payment_cookie_options',
			[
				'expires'  => $expire,
				'path'     => COOKIEPATH,
				'domain'   => COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
			]
		);

		$cookie_name = $this->get_payment_cookie_name();

		$is_cookie_set = setcookie( $cookie_name, $payment_id ?? '', $cookie_options ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie

		if ( $is_cookie_set ) {
			// Overwrite local variable, so we can use it right away.
			$_COOKIE[ $cookie_name ] = $payment_id; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		}

		if ( ! $payment_id ) {
			unset( $_COOKIE[ $cookie_name ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___COOKIE
		}

		return $is_cookie_set;
	}

	/**
	 * Updates an existing payment with order data.
	 *
	 * @since 5.24.0
	 *
	 * @param array   $data  The frontend data.
	 * @param WP_Post $order The order post object.
	 *
	 * @return array|null The payment data or error.
	 */
	public function update_payment( array $data, WP_Post $order ): ?array {
		if ( empty( $data['payment_source_id'] ) ) {
			return null;
		}

		$payment_id = $data['payment_source_id'];
		$payment    = Payment::get( $payment_id );

		if ( is_wp_error( $payment ) ) {
			return $payment;
		}

		if ( ! $payment ) {
			return null;
		}

		// Update the payment with order metadata.
		$metadata = $this->get_updated_metadata( $order, $payment );

		// If no changes needed, just return the current payment.
		if ( empty( $metadata ) ) {
			return $payment;
		}

		$update_data = [
			'metadata' => $metadata,
		];

		return []; // TODO: Implement update_payment() method.
	}

	/**
	 * Get updated metadata for a payment.
	 *
	 * @since 5.24.0
	 *
	 * @param WP_Post $order    The order post object.
	 * @param array   $payment  The payment data.
	 *
	 * @return array
	 */
	protected function get_updated_metadata( WP_Post $order, array $payment ): array {
		$metadata = $payment['metadata'] ?? [];

		$order_data = [
			'order_id'       => $order->ID,
			'site_url'       => home_url(),
			'site_name'      => get_bloginfo( 'name' ),
			'customer_email' => get_post_meta( $order->ID, Order::$purchaser_email_meta_key, true ),
			'customer_name'  => get_post_meta( $order->ID, Order::$purchaser_full_name_meta_key, true ),
		];

		// Don't add duplicate data.
		foreach ( $order_data as $key => $value ) {
			if ( isset( $metadata[ $key ] ) && $metadata[ $key ] === $value ) {
				unset( $order_data[ $key ] );
			}
		}

		// If no changes, return empty array.
		if ( empty( $order_data ) ) {
			return [];
		}

		return array_merge( $metadata, $order_data );
	}
}
