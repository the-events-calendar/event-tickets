<?php
/**
 * The REST API handler for the Onboarding Wizard.
 * Cleverly named...API.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */

namespace TEC\Tickets\Admin\Onboarding;

use TEC\Common\Admin\Onboarding\Abstract_API;

use WP_REST_Request as Request;
use WP_REST_Server as Server;
use WP_Error;
use WP_REST_Response;

/**
 * Class API
 *
 * @todo Move shared pieces to common.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin\Onboarding
 */
class API extends Abstract_API {

	/**
	 * The action for this nonce.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NONCE_ACTION = '_tec_tickets_wizard';

	/**
	 * Rest Endpoint namespace
	 *
	 * @since TBD
	 *
	 * @var  string
	 */
	protected const ROOT_NAMESPACE = 'tec/tickets/onboarding';

	/**
	 * Register the endpoint.
	 *
	 * @since TBD
	 *
	 * @return bool If we registered the endpoint.
	 */
	public function register(): bool {
		return register_rest_route(
			self::ROOT_NAMESPACE,
			'/wizard',
			[
				'methods'             => [ Server::CREATABLE ],
				'callback'            => [ $this, 'handle' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'action_nonce' => [
						'type'              => 'string',
						'description'       => __( 'The action nonce for the request.', 'event-tickets' ),
						'required'          => true,
						'validate_callback' => [ $this, 'check_nonce' ],
					],
				],
			]
		);
	}

	/**
	 * Check the nonce.
	 *
	 * @since TBD
	 *
	 * @param string $nonce The nonce.
	 *
	 * @return bool|WP_Error True if the nonce is valid, WP_Error if not.
	 */
	public function check_nonce( $nonce ) {
		$verified = wp_verify_nonce( $nonce, self::NONCE_ACTION );

		if ( $verified ) {
			return true;
		}

		return new WP_Error(
			'tec_invalid_nonce',
			__( 'Invalid nonce.', 'event-tickets' ),
			[ 'status' => 403 ]
		);
	}

	/**
	 * Check the permissions.
	 *
	 * @since TBD
	 *
	 * @return bool If the user has the correct permissions.
	 */
	public function check_permissions(): bool {
		$required_permission = 'manage_options';

		/**
		 * Filter the required permission for the onboarding wizard.
		 *
		 * @since TBD
		 *
		 * @param string $required_permission The required permission.
		 * @param API    $api The api object.
		 *
		 * @return string The required permission.
		 */
		$required_permission = (string) apply_filters( 'tec_tickets_onboarding_wizard_permissions', $required_permission, $this );

		return current_user_can( $required_permission );
	}

	/**
	 * Handle the request.
	 *
	 * @since TBD
	 *
	 * @param Request $request The request object.
	 *
	 * @return WP_REST_Response The response.
	 */
	public function handle( Request $request ): WP_REST_Response {
		/**
		 * Each step hooks in here and potentially modifies the response.
		 *
		 * @since TBD
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param Request          $request  The request object.
		 */
		return apply_filters( 'tec_tickets_onboarding_wizard_handle', $this->set_tab_records( $request ), $request );
	}

	/**
	 * Passes the request and data to the handler.
	 *
	 * @since TBD
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_REST_Response The response.
	 */
	protected function set_tab_records( $request ): WP_REST_Response {
		$params   = $request->get_params();
		$begun    = $params['begun'] ?? false;
		$finished = $params['finished'] ?? false;
		$skipped  = $params['skippedTabs'] ?? [];
		$complete = $params['completedTabs'] ?? [];

		// Remove any elements in $completed from $skipped.
		$skipped = array_values( array_diff( $skipped, $complete ) );

		if ( $begun ) {
			$complete = array_push( $complete, 0 );
		}

		if ( $finished ) {
			$begun = true;
		}

		// Set up our data for a single save.
		$settings                   = tribe( Data::class )->get_wizard_settings();
		$settings['begun']          = $begun;
		$settings['finished']       = $finished;
		$settings['current_tab']    = $params['currentTab'] ?? 0;
		$settings['completed_tabs'] = $this->normalize_tabs( $complete );
		$settings['skipped_tabs']   = $this->normalize_tabs( $skipped );

		// Stuff we don't want/need to store in the settings.
		unset(
			$params['timezones'],
			$params['countries'],
			$params['currencies'],
			$params['action_nonce'],
			$params['_wpnonce']
		);

		// Add a snapshot of the data from the last request.
		$settings['last_send'] = $params;

		// Update the option.
		$updated = tribe( Data::class )->update_wizard_settings( $settings );

		// We want to record the issue but we *don't* want to send back a failure since this part is not required for the user.
		return new WP_REST_Response(
			[
				'success' => true,
				'message' => $updated ? [ __( 'Onboarding wizard step completed successfully.', 'event-tickets' ) ] : [ __( 'Failed to update wizard settings.', 'event-tickets' ) ],
			],
			200
		);
	}

	/**
	 * Normalize the tabs. Remove duplicates
	 *
	 * @since TBD
	 *
	 * @param array<int> $tabs An array of tab indexes (int).
	 *
	 * @return array
	 */
	protected function normalize_tabs( $tabs ): array {
		// Filter out duplicates.
		$tabs = array_unique( (array) $tabs, SORT_NUMERIC );

		// Reindex the array.
		return array_values( $tabs );
	}
}
