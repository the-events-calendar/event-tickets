<?php

namespace TEC\Tickets\QR;

use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Observer
 *
 * @since   5.7.0
 *
 * @package TEC\Tickets\QR
 */
class Observer {
	/**
	 * Processes the links coming from QR codes and decides what to do:
	 *   - If the user is logged in and has proper permissions, it will redirect
	 *     to the attendees screen for the event, and will automatically check in the user.
	 *
	 *   - If the user is not logged in and/or does not have proper permissions, it will
	 *     redirect to the homepage of the event (front end single event view).
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function handle_checkin_redirect(): void {
		if ( ! tribe( Settings::class )->is_enabled() ) {
			return;
		}

		$is_qr_code_request = tribe_get_request_var( 'event_qr_code', false );

		// Not as fancy as a custom permalink handler, but way less likely to fail depending on setup and settings.
		if ( ! $is_qr_code_request ) {
			return;
		}

		$ticket_id = tribe_get_request_var( 'ticket_id', false );
		$event_id  = tribe_get_request_var( 'event_id', false );

		// Check all the data we need is there.
		if ( empty( $ticket_id ) || empty( $event_id ) ) {
			return;
		}

		$event_id      = (int) $event_id;
		$ticket_id     = (int) $ticket_id;
		$security_code = (string) esc_attr( tribe_get_request_var( 'security_code' ) );

		// See if the user had access or not to the checkin process.
		[ $url, $user_had_access ] = $this->authorized_check_in( $event_id, $ticket_id, $security_code );

		/**
		 * Filters the redirect URL if the user can access the QR checkin
		 *
		 * @param string $url             URL to redirect to, gets escaped upstream.
		 * @param int    $event_id        Event Post ID.
		 * @param int    $ticket_id       Ticket Post ID.
		 * @param bool   $user_had_access Whether the logged-in user has permission to perform check ins.
		 */
		$url = apply_filters_deprecated( 'tribe_tickets_plus_qr_handle_redirects', [ $url, $event_id, $ticket_id, $user_had_access ], '5.7.0', 'Use `tec_tickets_qr_observer_handle_handle_checkin_redirect` instead.' );

		/**
		 * Filters the redirect URL if the user can access the QR checkin.
		 *
		 * @since 5.7.0
		 *
		 * @param string $url             URL to redirect to, gets escaped upstream.
		 * @param int    $event_id        Event Post ID.
		 * @param int    $ticket_id       Ticket Post ID.
		 * @param bool   $user_had_access Whether the logged-in user has permission to perform check ins.
		 */
		$url = apply_filters( 'tec_tickets_qr_observer_handle_checkin_redirect', $url, $event_id, $ticket_id, $user_had_access );

		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Check if user is authorized to check in Ticket.
	 *
	 * @since 5.7.0
	 *
	 * @param int    $event_id      Event post ID.
	 * @param int    $ticket_id     Ticket post ID.
	 * @param string $security_code Ticket security code.
	 *
	 * @return array
	 */
	public function authorized_check_in( $event_id, $ticket_id, $security_code ): array {

		if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
			$checkin_arr = [
				'url'             => get_permalink( $event_id ),
				'user_had_access' => false,
			];

			return $checkin_arr;
		}

		$post = get_post( $event_id );

		if ( empty( $post ) ) {
			return [
				'url'             => '',
				'user_had_access' => true,
			];
		}

		$check_security_code = true;

		/**
		 * Filters the check for security code when checking in a ticket
		 *
		 * @since 4.11.2 Change the default to true.
		 * @deprecated 5.7.0 Use `tec_tickets_qr_observer_should_check_security_code` instead.
		 *
		 * @param bool $check_security_code The default is to check the security code.
		 */
		$check_security_code = apply_filters_deprecated( 'tribe_tickets_plus_qr_check_security_code', [ $check_security_code ], '5.7.0', );

		/**
		 * Filters the check for security code when checking in a ticket
		 *
		 * @since 5.7.0
		 *
		 * @param bool   $check_security_code The default is to check the security code.
		 * @param int    $ticket_id           The ticket ID.
		 * @param int    $event_id            The event ID.
		 * @param string $security_code       The security code.
		 */
		$check_security_code = apply_filters( 'tec_tickets_qr_observer_should_check_security_code', $check_security_code, $ticket_id, $event_id, $security_code );

		/** @var \Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		$service_provider = $data_api->get_ticket_provider( $ticket_id );

		// If check_security_code but security key does not match, do not check in and redirect with message.
		if (
			$check_security_code
			&& (
				empty( $service_provider->security_code )
				|| get_post_meta( $ticket_id, $service_provider->security_code, true ) !== $security_code
			)
		) {
			$url = add_query_arg(
				[
					'post_type'              => $post->post_type,
					'page'                   => tribe( 'tickets.attendees' )->slug(),
					'event_id'               => $event_id,
					'qr_checked_in'          => $ticket_id,
					'no_security_code_match' => true,
				],
				admin_url( 'edit.php' )
			);

			$checkin_arr = [
				'url'             => $url,
				'user_had_access' => true,
			];

			return $checkin_arr;
		}

		// If the user is the site owner (or similar), Check in the user to the event.
		$this->check_in( $ticket_id );

		$url = add_query_arg(
			[
				'post_type'     => $post->post_type,
				'page'          => tribe( 'tickets.attendees' )->slug(),
				'event_id'      => $event_id,
				'qr_checked_in' => $ticket_id,
			],
			admin_url( 'edit.php' )
		);

		$checkin_arr = [
			'url'             => $url,
			'user_had_access' => true,
		];

		return $checkin_arr;
	}

	/**
	 * Show a notice so the user knows the ticket was checked in.
	 *
	 * @since 5.7.0
	 *
	 * @return void
	 */
	public function legacy_handler_admin_notice(): void {
		if ( ! tribe( Settings::class )->is_enabled() ) {
			return;
		}

		if ( empty( $_GET['qr_checked_in'] ) ) {
			return;
		}

		// Use Human-readable ID Where Available for QR Check in Message.
		$ticket_id        = absint( $_GET['qr_checked_in'] );
		$no_match         = isset( $_GET['no_security_code_match'] ) ? absint( $_GET['no_security_code_match'] ) : false;
		$ticket_status    = get_post_status( $ticket_id );
		$checked_status   = get_post_meta( $ticket_id, '_tribe_qr_status', true );
		$ticket_unique_id = get_post_meta( $ticket_id, '_unique_id', true );
		$ticket_id        = $ticket_unique_id === '' ? $ticket_id : $ticket_unique_id;

		// If the attendee was deleted.
		if ( false === $ticket_status || 'trash' === $ticket_status ) {

			echo '<div class="error"><p>';
			printf(
				// Translators: %s is the ticket ID.
				esc_html__( 'The ticket with ID %s was deleted and cannot be checked-in.', 'event-tickets' ),
				esc_html( $ticket_id )
			);
			echo '</p></div>';

			// If Security Code does not match.
		} elseif ( $no_match ) {
			echo '<div class="error"><p>';
			printf(
				// Translators: %s is the ticket ID.
				esc_html__( 'The security code for ticket with ID %s does not match.', 'event-tickets' ),
				esc_html( $ticket_id )
			);
			echo '</p></div>';

			// If status is QR then display already checked-in warning.
		} elseif ( $checked_status ) {

			echo '<div class="error"><p>';
			printf(
				// Translators: %s is the ticket ID.
				esc_html__( 'The ticket with ID %s has already been checked in.', 'event-tickets' ),
				esc_html( $ticket_id )
			);
			echo '</p></div>';

			// Otherwise, just check-in like normal.
		} else {

			echo '<div class="updated"><p>';
			printf(
				// Translators: %s is the ticket ID.
				esc_html__( 'The ticket with ID %s was checked in.', 'event-tickets' ),
				esc_html( $ticket_id )
			);
			echo '</p></div>';

			// Update the checked-in status when using the QR code here.
			update_post_meta( absint( $_GET['qr_checked_in'] ), '_tribe_qr_status', 1 );
		}
	}

	/**
	 * Checks the user in, for all the *Tickets modules running.
	 *
	 * @since 5.7.0
	 *
	 * @param string|int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	protected function check_in( $ticket_id ): void {
		$modules = Tickets::modules();

		foreach ( $modules as $class => $module ) {
			$module_instance = Tickets::get_ticket_provider_instance( $class );

			if ( empty( $module_instance ) ) {
				continue;
			}

			$module_instance->checkin( $ticket_id, false );
		}
	}
}
