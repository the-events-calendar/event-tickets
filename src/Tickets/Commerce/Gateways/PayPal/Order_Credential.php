<?php
/**
 * Order-scoped credential for the PayPal gateway.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal
 */

namespace TEC\Tickets\Commerce\Gateways\PayPal;

/**
 * Issues and verifies the secret that lets a buyer finalize the PayPal order they just approved when
 * their cart cookie did not survive the round trip to PayPal.
 *
 * Stripe authorizes that case with the Payment Intent client secret. PayPal issues no equivalent: the
 * only value it hands the browser is the order id, which is also the value travelling in the capture
 * URL, so accepting it would reduce the gate to an existence check on the order. This mints a secret
 * of our own instead, hands it to the buyer's browser once when the order is created, and keeps only
 * a salted hash of it on the order.
 *
 * @since TBD
 */
final class Order_Credential {
	/**
	 * Meta key holding the salted hash of the credential issued for an order.
	 *
	 * @var string
	 */
	public const META_KEY = '_tec_tc_order_paypal_capture_credential';

	/**
	 * Request parameter the buyer's browser sends the credential back in.
	 *
	 * It travels in the request body rather than the URL, so it stays out of access logs and referrers.
	 *
	 * @var string
	 */
	public const REQUEST_PARAM = 'order_credential';

	/**
	 * Length, in characters, of a generated credential.
	 *
	 * @var int
	 */
	private const LENGTH = 64;

	/**
	 * Issues a credential for an order and stores its hash against the PayPal order it covers.
	 *
	 * Only the hash is persisted, so the stored value is of no use to anyone reading the order's meta.
	 *
	 * @since TBD
	 *
	 * @param int    $order_id         The Tickets Commerce order post ID.
	 * @param string $gateway_order_id The PayPal order id the credential is issued for.
	 *
	 * @return string The credential to hand the buyer's browser.
	 */
	public function issue( int $order_id, string $gateway_order_id ): string {
		$credential = wp_generate_password( self::LENGTH, false, false );

		update_post_meta( $order_id, self::META_KEY, $this->hash( $gateway_order_id, $credential ) );

		return $credential;
	}

	/**
	 * Whether a candidate is the credential issued for this order and this PayPal order id.
	 *
	 * @since TBD
	 *
	 * @param int    $order_id         The Tickets Commerce order post ID.
	 * @param string $gateway_order_id The PayPal order id the request is for.
	 * @param string $candidate        The credential the request carries.
	 *
	 * @return bool Whether the candidate is the credential issued for this order.
	 */
	public function matches( int $order_id, string $gateway_order_id, string $candidate ): bool {
		if ( '' === $candidate ) {
			return false;
		}

		$stored = get_post_meta( $order_id, self::META_KEY, true );

		if ( ! is_string( $stored ) || '' === $stored ) {
			return false;
		}

		return hash_equals( $stored, $this->hash( $gateway_order_id, $candidate ) );
	}

	/**
	 * Hashes a credential against the PayPal order it belongs to.
	 *
	 * The PayPal order id is part of the hashed material, so a credential issued before checkout was
	 * restarted cannot authorize the PayPal order that replaced it.
	 *
	 * wp_salt() keys the hash to the site, and hash_hmac() is called directly rather than through
	 * wp_hash() because wp_hash()'s algorithm argument postdates the WordPress version this plugin
	 * supports and would silently fall back to md5 there.
	 *
	 * @since TBD
	 *
	 * @param string $gateway_order_id The PayPal order id.
	 * @param string $credential       The credential to hash.
	 *
	 * @return string The salted hash stored for the credential.
	 */
	private function hash( string $gateway_order_id, string $credential ): string {
		return hash_hmac( 'sha256', $gateway_order_id . '|' . $credential, wp_salt( 'auth' ) );
	}
}
