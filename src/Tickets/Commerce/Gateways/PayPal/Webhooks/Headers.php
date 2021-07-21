<?php

namespace TEC\Tickets\Commerce\Gateways\PayPal\Webhooks;

use Exception;

/**
 * Class Headers.
 *
 * @since   5.1.6
 *
 * @package TEC\Tickets\Commerce\Gateways\PayPal\Webhooks
 */
class Headers {

	/**
	 * @since 5.1.6
	 * @var string
	 */
	public $transmission_id;

	/**
	 * @since 5.1.6
	 * @var string
	 */
	public $transmission_time;

	/**
	 * @since 5.1.6
	 * @var string
	 */
	public $transmission_sig;

	/**
	 * @since 5.1.6
	 * @var string
	 */
	public $cert_url;

	/**
	 * @since 5.1.6
	 * @var string
	 */
	public $auth_algo;

	/**
	 * This grabs the headers from the webhook request to be used in the signature verification
	 *
	 * A strange thing here is that the headers are inconsistent between live and sandbox mode, so this also checks for
	 * both forms of the headers (studly case and all caps).
	 *
	 * @since 5.1.6
	 *
	 * @throws \HttpHeaderException
	 *
	 * @param array $headers
	 *
	 * @return self
	 */
	public static function from_headers( array $headers ) {
		$header_keys = [
			'transmission_id'   => 'Paypal-Transmission-Id',
			'transmission_time' => 'Paypal-Transmission-Time',
			'transmission_sig'  => 'Paypal-Transmission-Sig',
			'cert_url'          => 'Paypal-Cert-Url',
			'auth_algo'         => 'Paypal-Auth-Algo',
		];

		$paypal_headers = new self();
		$missing_keys   = [];
		foreach ( $header_keys as $property => $key ) {
			if ( ! isset( $headers[ $key ] ) ) {
				$key = str_replace( '-', '_', $key );
				$key = strtoupper( $key );

				if ( ! isset( $headers[ $key ] ) ) {
					$key = strtolower( $key );
				}
			}

			if ( isset( $headers[ $key ] ) ) {
				$paypal_headers->{$property} = $headers[ $key ];
			} else {
				$missing_keys[] = $key;
			}
		}

		if ( ! empty( $missing_keys ) ) {
			tribe( 'logger' )->log_error(
				sprintf(
				// Translators: %s: The missing keys and header information.
					__( 'Missing PayPal webhook header: %s', 'event-tickets' ),
					json_encode( [
						'missingKeys' => $missing_keys,
						'headers'     => $headers,
					] )
				),
				'tickets-commerce-paypal-commerce'
			);

			throw new Exception( "Missing PayPal header: $key" );
		}

		return $paypal_headers;
	}
}
