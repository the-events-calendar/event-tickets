<?php
/**
 * Handles the settings step of the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding\Steps;

use TEC\Common\Admin\Onboarding\Steps\Abstract_Step;
use WP_REST_Response;
use WP_REST_Request;
use TEC\Tickets\Settings as Tickets_Commerce_Settings;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets\Admin\Onboarding\API;

/**
 * Class Settings
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Settings extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 1;

	/**
	 * Process the settings data.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function process( $response, $request ): WP_REST_Response {
		$settings = $request->get_json_params();

		if ( empty( $settings['currentTab'] ) ) {
			return $this->add_fail_message( $response, __( 'No settings provided.', 'event-tickets' ) );
		}

		tribe_update_option( Tickets_Commerce_Settings::$tickets_commerce_enabled, (bool) $settings['paymentOption'] );

		tribe_update_option( Currency::$currency_code_option, $settings['currency'] );

		// Update the option.
		$updated = tribe( API::class )->update_wizard_settings( $settings );

		return $this->add_message( $response, $updated ? __( 'Successfully saved settings.', 'event-tickets' ) : __( 'Failed to save settings.', 'event-tickets' ) );
	}
}
