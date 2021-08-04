<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\SignUp;

use TEC\Tickets\Commerce\Gateways\PayPal\WhoDat;

/**
 * Class Onboard
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */
class Onboard {

	/**
	 * Request the signup link that redirects the seller to PayPal.
	 *
	 * @since TBD
	 *
	 * @return string|false
	 */
	public function get_paypal_signup_link() {
		$return_url = $this->get_return_url();
		$signup     = tribe( WhoDat::class )->get_seller_signup_link( $return_url );

		if ( empty( $signup ) || ! isset( $signup->links[1]->href ) ) {
			return false;
		}

		return $signup->links[1]->href;
	}

	/**
	 * When the seller completes the sign-up flow, they are redirected to this return URL on their site.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_return_url() {
		$nonce = str_shuffle( uniqid( '', true ) . uniqid( '', true ) );

		update_option( 'tickets_commerce_nonce', $nonce );

		return add_query_arg( [
			'wp_nonce' => $nonce,
		], esc_url( rest_url() ) . 'tickets-commerce/paypal/on-boarding/' );
	}

	/**
	 * Verify if the seller was successfully onboarded.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_seller_status() {
		$saved_merchant_id = get_option( 'tickets_commerce_merchant_id_in_paypal' );

		if ( false === $saved_merchant_id ) {
			return 'inactive';
		}

		$seller_status = tribe( WhoDat::class )->get_seller_status( $saved_merchant_id );

		$payments_receivable = isset( $seller_status['payments_receivable'] ) ? $seller_status['payments_receivable'] : '';
		$paypal_product_name = isset( $seller_status['products'][0]['name'] ) ? $seller_status['products'][0]['name'] : '';
		$paypal_product_status = isset( $seller_status['products'][0]['status'] ) ? $seller_status['products'][0]['status'] : '';

		if ( true === $payments_receivable && 'EXPRESS_CHECKOUT' === $paypal_product_name && 'ACTIVE' === $paypal_product_status ) {
			return 'active';
		}

		return 'inactive';
	}

	/**
	 * Save the PayPal Seller data as WP Options.
	 *
	 * @since TBD
	 */
	public function save_paypal_seller_data( $request ) {
		$saved_nonce   = get_option( 'tickets_commerce_nonce' );
		$request_nonce = $request->get_param( 'wp_nonce' );
		$return_url    = add_query_arg( [
			'page'      => 'tribe-common',
			'tab'       => 'payments',
			'post_type' => 'tribe_events',
		], admin_url() . 'edit.php' );

		if ( $request_nonce !== $saved_nonce ) {
			delete_option( 'tickets_commerce_nonce' );
			wp_redirect( $return_url );
			exit;
		}

		$merchant_id           = $request->get_param( 'merchantId' );
		$merchant_id_in_paypal = $request->get_param( 'merchantIdInPayPal' );
		$permissions_granted   = $request->get_param( 'permissionsGranted' );
		$consent_status        = $request->get_param( 'consentStatus' );
		$account_status        = $request->get_param( 'accountStatus' );

		update_option( 'tickets_commerce_merchant_id', $merchant_id );
		update_option( 'tickets_commerce_merchant_id_in_paypal', $merchant_id_in_paypal );
		update_option( 'tickets_commerce_permissions_granted', $permissions_granted );
		update_option( 'tickets_commerce_consent_status', $consent_status );
		update_option( 'tickets_commerce_account_status', $account_status );
		delete_option( 'tickets_commerce_nonce' );

		wp_redirect( $return_url );
		exit;
	}

	/**
	 * Sanitize a request argument based on details registered to the route.
	 *
	 * @param  mixed  $value  Value of the 'filter' argument.
	 *
	 * @return string|array
	 */
	public function sanitize_callback( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}
}