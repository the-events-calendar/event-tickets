<?php

namespace TEC\Tickets\QR;

use WP_Error;

/**
 * Class Proxy
 *
 * @since   5.7.0
 *
 * @package TEC\Tickets\QR
 */
class Connector {
	/**
	 * The nonce action used to generate the QR Code API Hash.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_nonce_key(): string {
		return 'generate_qr_nonce';
	}

	/**
	 * The AJAX action used to generate the QR Code API Hash.
	 *
	 * @since 5.7.0
	 *
	 * @return string
	 */
	public function get_ajax_action_key(): string {
		return 'tec_tickets_qr_generate_api_key';
	}

	/**
	 * Get the connection QR code image data src.
	 *
	 * @since 5.7.0
	 *
	 * @return string|WP_Error The data url to the QR code image or WP_Error.
	 */
	public function get_base64_code_src() {
		$qr_code = tribe( QR::class );

		if ( is_wp_error( $qr_code ) ) {
			return $qr_code;
		}

		$json_encoded_data = wp_json_encode( $this->get_data() );

		return $qr_code->size( 6 )->get_png_as_base64( $json_encoded_data );
	}

	/**
	 * Generates the link for the QR image.
	 *
	 * @since 5.7.0
	 *
	 * @param int|string $ticket_id The ticket ID.
	 * @param int|string $event_id The Event ID.
	 * @param string     $security_code The security code.
	 *
	 * @return string
	 */
	public function get_checkin_url( $ticket_id, $event_id, string $security_code ): string {
		$base_url = home_url( '/' );

		/**
		 * Allows filtering the base URL which QR code query args are appended to. Defaults to
		 * the site's home_url() with a trailing slash.
		 *
		 * @since      4.7.3
		 * @deprecated 5.7.0 Use `tec_tickets_qr_code_base_url` instead.
		 *
		 * @param string     $base_url
		 * @param int|string $ticket_id
		 * @param int|string $event_id
		 */
		$base_url = apply_filters_deprecated( 'tribe_tickets_qr_code_base_url', [ $base_url, $ticket_id, $event_id ], '5.7.0', 'Use `tec_tickets_qr_code_base_url` instead.' );
		/**
		 * Allows filtering the base URL which QR code query args are appended to. Defaults to
		 * the site's home_url() with a trailing slash.
		 *
		 * @since 5.7.0
		 *
		 * @param string     $base_url      The base URL.
		 * @param int|string $ticket_id     The ticket ID.
		 * @param int|string $event_id      The event ID.
		 * @param string     $security_code The security code from the attendee.
		 */
		$base_url = apply_filters( 'tec_tickets_qr_code_base_url', $base_url, $ticket_id, $event_id, $security_code );

		$query_args = [
			'event_qr_code' => 1,
			'ticket_id'     => $ticket_id,
			'event_id'      => $event_id,
			'security_code' => $security_code,
			'path'          => urlencode( tribe_tickets_rest_url_prefix() . '/qr' ),
		];

		$url = add_query_arg( $query_args, $base_url );

		/**
		 * Allows filtering the checkin url.
		 *
		 * @since 5.7.0
		 *
		 * @param string     $url
		 * @param int|string $ticket_id
		 * @param int|string $event_id
		 * @param string     $security_code
		 */
		return apply_filters( 'tec_tickets_qr_code_checkin_url', $url, $ticket_id, $event_id, $security_code );
	}

	/**
	 * Get QR image from ticket.
	 *
	 * @since 5.7.0
	 *
	 * @param array $ticket Ticket data we are using to get the QR code image from.
	 *
	 * @return ?string
	 */
	public function get_image_from_ticket_data( $ticket ): ?array {
		if ( ! tribe( Settings::class )->is_enabled( $ticket ) ) {
			return null;
		}

		$link   = $this->get_checkin_url( $ticket['qr_ticket_id'], $ticket['event_id'], $ticket['security_code'] );
		$upload = $this->get_image_for_link( $link );

		if ( ! $upload ) {
			return null;
		}

		return $upload;
	}

	/**
	 * Generates the QR image for a given link and stores it in /wp-content/uploads.
	 * Returns the link to the new image.
	 *
	 * @param ?string $link The QR link.
	 *
	 * @return ?array
	 */
	public function get_image_for_link( ?string $link ): ?array {
		$qr_code = tribe( QR::class );

		if ( empty( $link ) ) {
			return null;
		}

		if ( is_wp_error( $qr_code ) ) {
			return null;
		}

		$file_name = 'qr_' . md5( $link );
		$upload    = $qr_code->get_png_as_file( $link, $file_name, '' );

		if ( ! empty( $upload['error'] ) ) {
			return null;
		}

		return $upload;
	}

	/**
	 * Get QR Code URL from ticket.
	 *
	 * @since 5.7.0
	 *
	 * @param array $ticket Ticket data we are using to get the QR code image from.
	 *
	 * @return ?string
	 */
	public function get_image_url_from_ticket_data( $ticket ): ?string {
		$upload = $this->get_image_from_ticket_data( $ticket );

		return ! empty( $upload['url'] ) ? $upload['url'] : null;
	}

	/**
	 * Generates the QR image for a given link and stores it in /wp-content/uploads.
	 * Returns the link to the new image.
	 *
	 * @param ?string $link The QR link.
	 *
	 * @return ?string
	 */
	public function get_image_url_for_link( ?string $link ): ?string {
		$upload = $this->get_image_for_link( $link );

		return ! empty( $upload['url'] ) ? $upload['url'] : null;
	}

	/**
	 * Get QR Code file path from ticket.
	 *
	 * @since 5.7.0
	 *
	 * @param array $ticket Ticket data we are using to get the QR code image from.
	 *
	 * @return ?string
	 */
	public function get_image_path_from_ticket_data( $ticket ): ?string {
		$upload = $this->get_image_from_ticket_data( $ticket );

		return ! empty( $upload['file'] ) ? $upload['file'] : null;
	}

	/**
	 * Generates the QR image for a given link and stores it in /wp-content/uploads.
	 * Returns the QR image path to the new image.
	 *
	 * @param ?string $link The QR link.
	 *
	 * @return ?string
	 */
	public function get_image_path_for_link( ?string $link ): ?string {
		$upload = $this->get_image_for_link( $link );

		return ! empty( $upload['file'] ) ? $upload['file'] : null;
	}

	/**
	 * Gets the data used to engage with external connections to the site.
	 *
	 * @since 5.7.0
	 *
	 * @return array
	 */
	protected function get_data(): array {
		$data = [
			'url'     => site_url(),
			'api_key' => tribe( Settings::class )->get_api_key(),
			'tec'     => tec_tickets_tec_events_is_active(),
		];

		/**
		 * Filters the data that will be used to generate the QR code for connection purposes.
		 *
		 * @since 5.7.0
		 *
		 * @param array $data The data including the URL, API Key and TEC status by default.
		 */
		return (array) apply_filters( 'tec_tickets_qr_connector_data', $data );
	}

	/**
	 * Handles the regeneration of the QR Code API Hash via Admin AJAX request.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function handle_ajax_generate_api_key(): void {
		$confirm = tribe_get_request_var( 'confirm', false );

		if ( ! $confirm || ! wp_verify_nonce( $confirm, $this->get_nonce_key() ) ) {
			wp_send_json_error( __( 'Permission Error', 'event-tickets' ) );
		}

		$deleted_existing_hash = tribe( Settings::class )->delete_api_key();
		if ( false === $deleted_existing_hash && current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'The QR API key could not be deleted, please try again.', 'event-tickets' ) );
		}

		$api_key = tribe( Settings::class )->get_api_key();
		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'The QR API key was not regenerated, please try again.', 'event-tickets' ) );
		}

		$qr_src = $this->get_base64_code_src();

		if ( empty( $qr_src ) || is_wp_error( $qr_src ) ) {
			wp_send_json_error( __( 'The QR API key was generated, but generating the connection QR Code image failed.', 'event-tickets' ) );
		}

		$data = [
			'msg'    => __( 'QR API Key Generated', 'event-tickets' ),
			'key'    => $api_key,
			'qr_src' => $qr_src,
		];

		wp_send_json_success( $data );
	}
}
