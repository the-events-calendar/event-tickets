<?php
/**
 * Modifiers for the Stripe payment gateway.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */

namespace TEC\Tickets\Admin\Onboarding;

/**
 * Modifiers for the Stripe payment gateway.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */
class Stripe_Modifiers {

	/**
	 * Get the list of countries that are supported by Stripe.
	 *
	 * @since TBD
	 *
	 * @return array The list of countries.
	 */
	public function get_supported_countries(): array {
		$countries = [
			'AU' => 'Australia',
			'AT' => 'Austria',
			'BE' => 'Belgium',
			'BR' => 'Brazil',
			'BG' => 'Bulgaria',
			'CA' => 'Canada',
			'CI' => 'Côte d\'Ivoire',
			'HR' => 'Croatia',
			'CY' => 'Cyprus',
			'CZ' => 'Czech Republic',
			'DK' => 'Denmark',
			'EE' => 'Estonia',
			'FI' => 'Finland',
			'FR' => 'France',
			'DE' => 'Germany',
			'GH' => 'Ghana',
			'GI' => 'Gibraltar',
			'GR' => 'Greece',
			'HK' => 'Hong Kong',
			'HU' => 'Hungary',
			'IN' => 'India',
			'ID' => 'Indonesia',
			'IE' => 'Ireland',
			'IT' => 'Italy',
			'JP' => 'Japan',
			'KE' => 'Kenya',
			'LV' => 'Latvia',
			'LI' => 'Liechtenstein',
			'LT' => 'Lithuania',
			'LU' => 'Luxembourg',
			'MY' => 'Malaysia',
			'MT' => 'Malta',
			'MX' => 'Mexico',
			'NL' => 'Netherlands',
			'NZ' => 'New Zealand',
			'NG' => 'Nigeria',
			'NO' => 'Norway',
			'PL' => 'Poland',
			'PT' => 'Portugal',
			'RO' => 'Romania',
			'SG' => 'Singapore',
			'SK' => 'Slovakia',
			'SI' => 'Slovenia',
			'ZA' => 'South Africa',
			'ES' => 'Spain',
			'SE' => 'Sweden',
			'CH' => 'Switzerland',
			'TH' => 'Thailand',
			'AE' => 'United Arab Emirates',
			'GB' => 'United Kingdom',
			'US' => 'United States',
		];

		return apply_filters( 'tec_tickets_stripe_supported_countries', $countries );
	}
}
