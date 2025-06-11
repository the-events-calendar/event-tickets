<?php
/**
 * Welcome step for the onboarding wizard.
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding\Steps;

use TEC\Common\Admin\Onboarding\Steps\Abstract_Step;
use TEC\Common\Telemetry\Telemetry as Common_Telemetry;
use TEC\Tickets\Settings;
use WP_REST_Response;
use WP_REST_Request;

/**
 * Welcome step for the onboarding wizard.
 *
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Welcome extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since 5.23.0
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 0;

	/**
	 * Process the welcome step data.
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function process( $response, $request ): WP_REST_Response {
		$current_optin = tribe_get_option( 'opt-in-status', false );
		$optin         = $request->get_param( 'optin' );

		if ( $current_optin === $optin ) {
			return $this->add_message( $response, __( 'Opt-in status is already set to the requested value.', 'event-tickets' ) );
		}

		// Save the option.
		$option = tribe_update_option( 'opt-in-status', $optin );

		// Enable Tickets Commerce.
		$commerce_optin = tribe_update_option( Settings::$tickets_commerce_enabled, $optin );

		if ( ! $option ) {
			return $this->add_message( $response, __( 'Failed to save opt-in status.', 'event-tickets' ) );
		}

		// Tell Telemetry to update.
		tribe( Common_Telemetry::class )->register_tec_telemetry_plugins( $optin );

		return $this->add_message( $response, __( 'Successfully saved opt-in status.', 'event-tickets' ) );
	}
}
