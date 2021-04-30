<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\DataTransferObjects;

use Exception;

/**
 * Class PayPalWebhookHeaders.
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\SDK\DataTransferObjects
 */
class PayPalWebhookHeaders {

	/**
	 * @since TBD
	 * @var string
	 */
	public $transmissionId;

	/**
	 * @since TBD
	 * @var string
	 */
	public $transmissionTime;

	/**
	 * @since TBD
	 * @var string
	 */
	public $transmissionSig;

	/**
	 * @since TBD
	 * @var string
	 */
	public $certUrl;

	/**
	 * @since TBD
	 * @var string
	 */
	public $authAlgo;

	/**
	 * This grabs the headers from the webhook request to be used in the signature verification
	 *
	 * A strange thing here is that the headers are inconsistent between live and sandbox mode, so this also checks for
	 * both forms of the headers (studly case and all caps).
	 *
	 * @since TBD
	 *
	 * @param array $headers
	 *
	 * @return self
	 * @throws HttpHeaderException
	 */
	public static function fromHeaders( array $headers ) {
		$headerKeys = [
			'transmissionId'   => 'Paypal-Transmission-Id',
			'transmissionTime' => 'Paypal-Transmission-Time',
			'transmissionSig'  => 'Paypal-Transmission-Sig',
			'certUrl'          => 'Paypal-Cert-Url',
			'authAlgo'         => 'Paypal-Auth-Algo',
		];

		$payPalHeaders = new self();
		$missingKeys   = [];
		foreach ( $headerKeys as $property => $key ) {
			if ( ! isset( $headers[ $key ] ) ) {
				$key = str_replace( '-', '_', $key );
				$key = strtoupper( $key );

				if ( ! isset( $headers[ $key ] ) ) {
					$key = strtolower( $key );
				}
			}

			if ( isset( $headers[ $key ] ) ) {
				$payPalHeaders->$property = $headers[ $key ];
			} else {
				$missingKeys[] = $key;
			}
		}

		if ( ! empty( $missingKeys ) ) {
			tribe( 'logger' )->log_error(
				sprintf(
					// Translators: %s: The missing keys and header information.
					__( 'Missing PayPal webhook header: %s', 'event-tickets' ),
					json_encode( [
						'missingKeys' => $missingKeys,
						'headers'     => $headers,
					] )
				),
				'tickets-commerce-paypal-commerce'
			);

			throw new Exception( "Missing PayPal header: $key" );
		}

		return $payPalHeaders;
	}
}
