<?php
/**
 * Handles the communication step of the onboarding wizard.
 *
 * @since 5.23.0
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
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Communication extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since 5.23.0
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 3;

	/**
	 * Process the communication data.
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function process( $response, $request ): WP_REST_Response {
		$settings = $request->get_json_params();

		if ( empty( $settings['currentTab'] ) || $settings['currentTab'] < self::TAB_NUMBER ) {
			return $this->add_message( $response, __( 'No communication settings provided.', 'event-tickets' ) );
		}

		$email = $settings['userEmail'] ?? '';
		tribe_update_option( Settings::$option_sender_email, $email );

		$sender_name = $settings['userName'] ?? '';
		tribe_update_option( Settings::$option_sender_name, $sender_name );

		$updated = tribe( API::class )->update_wizard_settings( $settings );

		return $this->add_message( $response, $updated ? __( 'Successfully saved communication settings.', 'event-tickets' ) : __( 'Failed to save communication settings.', 'event-tickets' ) );
	}
}
