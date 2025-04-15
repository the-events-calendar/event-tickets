<?php
/**
 * Modifiers for the Square payment gateway.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */

namespace TEC\Tickets\Admin\Onboarding;

class Square_Modifiers {

	/**
	 * Get the list of countries that are supported by Square.
	 *
	 * @since TBD
	 *
	 * @return array The list of countries.
	 */
	public function get_supported_countries(): array {
		$countries = [
			'US' => 'United States',
			'CA' => 'Canada',
			'AU' => 'Australia',
			'JP' => 'Japan',
			'GB' => 'United Kingdom',
			'IE' => 'Ireland',
			'FR' => 'France',
			'ES' => 'Spain',
		];

		return apply_filters( 'tec_tickets_square_supported_countries', $countries );
	}


}
