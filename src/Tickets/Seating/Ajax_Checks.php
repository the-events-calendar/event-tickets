<?php
/**
 * Provides common methods to check AJAX requests.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Tickets\Seating\Admin\Ajax;

/**
 * Class Ajax_Checks.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Seating;
 */
trait Ajax_Checks {
	/**
	 * Checks if the current user can perform the requested AJAX action.
	 *
	 * @since TBD
	 *
	 * @param string $capability         The capability to check.
	 * @param mixed  ...$capability_args Optional arguments to pass to the capability check.
	 *
	 * @return bool Whether the current user can perform the requested AJAX action.
	 */
	private function check_current_ajax_user_can( string $capability = 'manage_options', ...$capability_args ): bool {
		if ( ! check_ajax_referer( Ajax::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Nonce verification failed', 'event-tickets' ),
				],
				401
			);

			return false;
		}

		if ( ! current_user_can( $capability, ...$capability_args ) ) {
			wp_send_json_error(
				[
					'error' => __( 'You do not have permission to perform this action.', 'event-tickets' ),
				],
				403
			);

			return false;
		}

		return true;
	}
}