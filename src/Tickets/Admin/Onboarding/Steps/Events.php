<?php
/**
 * Handles the events step of the onboarding wizard.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */

namespace TEC\Tickets\Admin\Onboarding\Steps;

use TEC\Common\Admin\Onboarding\Steps\Abstract_Step;
use WP_REST_Response;
use TEC\Common\StellarWP\Installer\Handler\Plugin;
use TEC\Tickets\Admin\Onboarding\API;
/**
 * Class Events
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Events extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 4;

	/**
	 * Process the events data.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function process( $response, $request ): WP_REST_Response {
		$params = $request->get_params();

		$updated = tribe( API::class )->update_wizard_settings( $params );

		return $this->install_events_calendar_plugin( $response, $request );
	}

	/**
	 * Install and activate The Events Calendar plugin from the WordPress.org repo.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function install_events_calendar_plugin( $response, $request ): WP_REST_Response {
		$params = $request->get_params();

		if ( ! isset( $params['events-calendar-installed'] ) || ! isset( $params['events-calendar-active'] ) ) {
			return $this->add_message( $response, __( 'Events Calendar install not requested.', 'event-tickets' ) );
		}

		$installed = $params['events-calendar-installed'];
		$activated = $params['events-calendar-active'];

		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		require_once ABSPATH . '/wp-admin/includes/file.php';

		// Check if the plugin is already installed and active.
		if ( $installed && $activated ) {
			return $this->add_message( $response, __( 'The Events Calendar plugin already installed and activated.', 'event-tickets' ) );
		}

		$plugin = new Plugin( 'The Events Calendar', 'the-events-calendar' );

		if ( ! $installed ) {
			$install = $plugin->install();

			if ( ! $install ) {
				return $this->add_fail_message( $response, __( 'Failed to install plugin.', 'event-tickets' ) );
			}
		}

		if ( ! $activated ) {
			$active = $plugin->activate();

			if ( ! $active ) {
				return $this->add_fail_message( $response, __( 'Failed to activate plugin.', 'event-tickets' ) );
			}
		}

		return $this->add_message( $response, __( 'The Events Calendar plugin installed and activated.', 'event-tickets' ) );
	}
}
