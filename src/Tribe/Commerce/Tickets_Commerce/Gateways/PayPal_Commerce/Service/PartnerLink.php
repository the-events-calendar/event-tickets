<?php

namespace Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Service;

/**
 * Class PartnerLink.
 *
 * This handles interacting with the PayPal API to generate a Partner Link.
 *
 * @see   https://developer.paypal.com/docs/platforms/seller-onboarding/build-onboarding/#step-1-generate-a-paypal-sign-up-link
 *
 * @since TBD
 *
 * @package Tribe\Tickets\Commerce\Tickets_Commerce\Gateways\PayPal_Commerce\Service
 */
class PartnerLink extends ApiService {

	/**
	 * List of countries that support custom payment accounts.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private $custom_payment_countries = [
		'AU' => 'AU',
		'AT' => 'AT',
		'BE' => 'BE',
		'BG' => 'BG',
		'CY' => 'CY',
		'CZ' => 'CZ',
		'DK' => 'DK',
		'EE' => 'EE',
		'FI' => 'FI',
		'FR' => 'FR',
		'GR' => 'GR',
		'HU' => 'HU',
		'IT' => 'IT',
		'LV' => 'LV',
		'LI' => 'LI',
		'LT' => 'LT',
		'LU' => 'LU',
		'MT' => 'MT',
		'NL' => 'NL',
		'NO' => 'NO',
		'PL' => 'PL',
		'PT' => 'PT',
		'RO' => 'RO',
		'SK' => 'SK',
		'SI' => 'SI',
		'ES' => 'ES',
		'SE' => 'SE',
		'GB' => 'GB',
		'US' => 'US',
	];

	/**
	 * The URL to return the Seller to.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $return_url;

	/**
	 * The country the Seller is in.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $country_code;

	/**
	 * Sets the return url for the link.
	 *
	 * @since TBD
	 *
	 * @param $return_url
	 *
	 * @return self The service object.
	 */
	public function set_return_url( $return_url ) {
		if ( ! filter_var( $return_url, FILTER_VALIDATE_URL ) ) {
			throw new InvalidArgumentException( "Invalid return URL provided: {$return_url}" );
		}

		$this->return_url = $return_url;

		return $this;
	}

	/**
	 * Sets the country code for the link
	 *
	 * @since TBD
	 *
	 * @param string $country_code
	 *
	 * @return self The service object.
	 */
	public function set_country_code( $country_code ) {
		if ( strlen( $country_code ) !== 2 ) {
			throw new InvalidArgumentException( "$country_code must be a 2 character country code" );
		}

		$this->country_code = strtoupper( $country_code );

		return $this;
	}

	/**
	 * Retrieves the Partner Link from PayPal and returns the link
	 *
	 * @since TBD
	 *
	 * @return array Partner link information.
	 *
	 * @throws Exception
	 */
	public function get_partner_link() {
		$token = $this->get_token();

		$request = curl_init();

		$nonce = str_shuffle( uniqid( '', true ) . uniqid( '', true ) );

		$product = $this->can_use_custom_payments() ? 'PPCP' : 'EXPRESS_CHECKOUT';

		$post_fields = [
			'operations'              => [
				[
					'operation'                  => 'API_INTEGRATION',
					'api_integration_preference' => [
						'rest_api_integration' => [
							'integration_method'  => 'PAYPAL',
							'integration_type'    => 'FIRST_PARTY',
							'first_party_details' => [
								'features'     => [
									'PAYMENT',
									'REFUND',
								],
								'seller_nonce' => $nonce,
							],
						],
					],
				],
			],
			'products'                => [
				$product
			],
			'partner_config_override' => [
				'return_url' => $this->return_url,
			],
			'legal_consents'          => [
				[
					'type'    => 'SHARE_DATA_CONSENT',
					'granted' => true,
				],
			],
		];

		curl_setopt_array( $request, [
			CURLOPT_URL            => $this->get_api_url( 'v2/customer/partner-referrals' ),
			CURLOPT_HTTPHEADER     => [
				'Accept: application/json',
				"Authorization: Bearer {$token}",
				'Content-Type: application/json',
			],
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST           => 1,
			CURLOPT_POSTFIELDS     => json_encode( $post_fields ),
		] );

		$response = curl_exec( $request );

		$error_response = curl_errno( $request ) ? curl_error( $response ) : null;

		curl_close( $request );

		if ( null !== $error_response ) {
			throw new Exception( 'Error: ', $error_response );
		}

		$response = json_decode( $response );

		if ( ! isset( $response->links[1]->href ) ) {
			throw new Exception( 'Unexpected response: ' . var_export( $response, true ) );
		}

		/**
		 * Response contains data for two links, but we only need the onboard link.
		 *
		 * $response->links[0] is the API detail for the referral request.
		 * Example: https://api-m.sandbox.paypal.com/v2/customer/partner-referrals/ABCXYZ==
		 *
		 * $response->links[1] is the link the seller will onboard from.
		 * Example: https://www.sandbox.paypal.com/us/merchantsignup/partner/onboardingentry?token=ABCXYZ==
		 *
		 * @see https://developer.paypal.com/docs/platforms/seller-onboarding/build-onboarding/#sample-response
		 */
		$onboarding_link = $response->links[1]->href;

		return [
			'nonce'       => $nonce,
			'partnerLink' => $onboarding_link,
			'product'     => $product,
		];
	}

	/**
	 * Determine whether or not the country is in a list where custom payments are supported.
	 *
	 * @since TBD
	 *
	 * @return bool Whether custom payments can be used.
	 */
	private function can_use_custom_payments() {
		return isset( $this->custom_payment_countries[ $this->country_code ] );
	}
}
