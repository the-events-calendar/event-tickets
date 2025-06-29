<?php
/**
 * Handles the events step of the onboarding wizard.
 *
 * @since 5.23.0
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
 * @since 5.23.0
 *
 * @package TEC\Tickets\Admin\Onboarding\Steps
 */
class Events extends Abstract_Step {
	/**
	 * The tab number for this step.
	 *
	 * @since 5.23.0
	 *
	 * @var int
	 */
	public const TAB_NUMBER = 4;

	/**
	 * Process the tec installation data.
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
			return $response;
		}

		$updated = tribe( API::class )->update_wizard_settings( $settings );

		return $this->install_events_calendar_plugin( $response, $request );
	}

	/**
	 * Install and activate The Events Calendar plugin from the WordPress.org repo.
	 *
	 * @since 5.23.0
	 *
	 * @param WP_REST_Response $response The response object.
	 * @param WP_REST_Request  $request  The request object.
	 *
	 * @return WP_REST_Response
	 */
	public function install_events_calendar_plugin( $response, $request ): WP_REST_Response {
		$params = $request->get_params();

		$installed = $params['tecInstalled'] ?? false;
		$activated = $params['tecActive'] ?? false;
		$install_requested = $params['eventsCalendar'] ?? false;

		if ( ! $install_requested ) {
			return $this->add_message( $response, __( 'The Events Calendar plugin was not requested to be installed.', 'event-tickets' ) );
		}

		// Check if the plugin is already installed and active.
		if ( $installed && $activated ) {
			return $this->add_message( $response, __( 'The Events Calendar plugin already installed and activated.', 'event-tickets' ) );
		}

		require_once ABSPATH . '/wp-admin/includes/plugin.php';
		require_once ABSPATH . '/wp-admin/includes/file.php';


		$plugin = new Plugin( 'The Events Calendar', 'the-events-calendar' );

		if ( ! $installed ) {
			$install = $plugin->install();

			if ( ! $install ) {
				return $this->add_message( $response, __( 'Failed to install plugin.', 'event-tickets' ) );
			}
		}

		if ( ! $activated ) {
			$active = $plugin->activate();

			if ( ! $active ) {
				return $this->add_message( $response, __( 'Failed to activate plugin.', 'event-tickets' ) );
			}
		}

		return $this->add_message( $response, __( 'The Events Calendar plugin installed and activated.', 'event-tickets' ) );
	}
}
