<?php
/**
 * Handles the communication step of the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding\Steps;

use TEC\Common\Admin\Onboarding\Steps\Abstract_Step;
use WP_REST_Response;
use WP_REST_Request;
use TEC\Tickets\Emails\Admin\Settings;
use TEC\Tickets\Admin\Onboarding\API;
/**
 * Class Communication
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Communication extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 3;

	/**
	 * Process the communication data.
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
			return $this->add_fail_message( $response, __( 'No communication settings provided.', 'event-tickets' ) );
		}

		tribe_update_option( Settings::$option_sender_email, $settings['email'] );

		tribe_update_option( Settings::$option_sender_name, $settings['senderName'] );

		$updated = tribe( API::class )->update_wizard_settings( $settings );

		return $this->add_message( $response, $updated ? __( 'Successfully saved communication settings.', 'event-tickets' ) : __( 'Failed to save communication settings.', 'event-tickets' ) );
	}
}
