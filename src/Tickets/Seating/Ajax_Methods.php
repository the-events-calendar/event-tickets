<?php
/**
 * Provides common methods to check AJAX requests.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating;
 */

namespace TEC\Tickets\Seating;

use TEC\Tickets\Seating\Admin\Ajax;

/**
 * Class Ajax_Methods.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating;
 */
trait Ajax_Methods {
	/**
	 * Checks if the current user can perform the requested AJAX action.
	 *
	 * @since 5.16.0
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

	/**
	 * Returns the request body.
	 *
	 * @since 5.16.0
	 *
	 * @return string The request body.
	 */
	private function get_request_body(): string {
		if ( function_exists( 'wpcom_vip_file_get_contents' ) ) {
			$body = wpcom_vip_file_get_contents( 'php://input' );
		} else {
			// phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsRemoteFile
			$body = trim( file_get_contents( 'php://input' ) );
		}

		return $body;
	}

	/**
	 * Returns the request body as JSON.
	 *
	 * @since 5.16.0
	 *
	 * @return array<mixed>|null Either the request body as JSON or `null` if the request body is empty
	 *                           or invalid JSON.
	 */
	private function get_request_json(): ?array {
		$body = $this->get_request_body();

		if ( empty( $body ) ) {
			return null;
		}

		return json_decode( $body, true );
	}
}
