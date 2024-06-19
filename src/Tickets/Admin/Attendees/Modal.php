<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since   5.9.1
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Class Modal.
 *
 * @since   5.9.1
 *
 * @package TEC\Tickets\Admin
 */
class Modal {
	/**
	 * Modal ID.
	 *
	 * @since 5.9.1
	 *
	 * @var string
	 */
	public static $modal_id = 'tec-tickets__attendee-details-dialog';

	/**
	 * Modal target.
	 *
	 * @since 5.9.1
	 *
	 * @var string
	 */
	public static $modal_target = 'tec-tickets__attendee-details-dialog';

	/**
	 * Check if we should render the modal.
	 *
	 * @since 5.9.1
	 *
	 * @return boolean Whether we should render the modal.
	 */
	public function should_render(): bool {
		return tribe( Page::class )->is_on_page() || tribe_get_request_var( 'page' ) === 'tickets-attendees';
	}

	/**
	 * Render the `Attendees` preview modal.
	 *
	 * @since 5.9.1
	 */
	public function render_modal() {
		if ( ! $this->should_render() ) {
			return;
		}

		// Enqueue `Attendees` assets.
		tribe_asset_enqueue_group( Assets::$group_key );

		tribe_asset_enqueue_group( 'tribe-tickets-admin' );

		// Render the modal contents.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->get_modal_content();
	}

	/**
	 * Get the default modal args.
	 *
	 * @since 5.10.0
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return array The default modal args.
	 */
	public function get_modal_args( $args = [] ): array {
		$default_args = [
			'append_target'           => '#' . static::$modal_target,
			'button_display'          => false,
			'close_event'             => 'tribeDialogCloseAttendeeDetailsModal.tribeTickets',
			'show_event'              => 'tribeDialogShowAttendeeDetailsModal.tribeTickets',
			'content_wrapper_classes' => 'tribe-dialog__wrapper tribe-modal__wrapper--attendee-details tribe-tickets__admin-container tribe-common event-tickets',
			'title'                   => esc_html__( 'Attendee Details', 'event-tickets' ),
			'title_classes'           => [
				'tribe-dialog__title',
				'tribe-modal__title',
				'tribe-common-h4',
				'tribe-modal__title--attendee-details',
			],
		];

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Get the default modal contents.
	 *
	 * @since 5.10.0
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return string The modal content.
	 */
	public function get_modal_content( $args = [] ): string {
		/** @var Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$content = $template->template( 'v2/components/loader/loader', [], false );

		$args = $this->get_modal_args( $args );

		$dialog_view = tribe( 'dialog.view' );

		ob_start();
		$dialog_view->render_modal( $content, $args, static::$modal_id );
		$modal_content = ob_get_clean();

		$modal  = '<div class="tribe-common event-tickets">';
		$modal .= '<span id="' . esc_attr( static::$modal_target ) . '"></span>';
		$modal .= $modal_content;
		$modal .= '</div>';

		return $modal;
	}

	/**
	 * Get the default modal button args.
	 *
	 * @since 5.10.0
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return array The default modal button args.
	 */
	public static function get_modal_button_args( $args = [] ): array {
		$default_args = [
			'id'                      => static::$modal_id,
			'append_target'           => '#' . static::$modal_target,
			'button_classes'          => [ 'button', 'action', 'button-primary', 'tec-tickets__admin-settings-attendee-details-button' ],
			'button_attributes'       => [ 'data-modal-title' => esc_html__( 'Attendee Details', 'event-tickets' ) ],
			'button_display'          => true,
			'button_id'               => 'tec-tickets__admin-attendee-details-' . uniqid(),
			'button_name'             => 'tec-tickets-attendee-details',
			'button_text'             => esc_attr_x( 'Attendee Details', 'Preview email button on the settings', 'event-tickets' ),
			'button_type'             => 'button',
			'close_event'             => 'tribeDialogCloseAttendeeDetailsModal.tribeTickets',
			'show_event'              => 'tribeDialogShowAttendeeDetailsModal.tribeTickets',
			'content_wrapper_classes' => 'tribe-dialog__wrapper event-tickets tribe-common',
			'title'                   => esc_html__( 'Attendee Details', 'event-tickets' ),
			'title_classes'           => [
				'tribe-dialog__title',
				'tribe-modal__title',
				'tribe-common-h5',
				'tribe-modal__title--attendee-details',
			],
		];

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Get the default modal button.
	 *
	 * @since 5.10.0
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return string The modal button.
	 */
	public static function get_modal_button( $args = [] ): string {
		$args        = self::get_modal_button_args( $args );
		$dialog_view = tribe( 'dialog.view' );

		return $dialog_view->template( 'button', $args, false );
	}

	/**
	 * Get the `Attendee Details` modal content,
	 * depending on the request.
	 *
	 * @since 5.10.0
	 *
	 * @param string|\WP_Error $render_response The render response HTML content or WP_Error with list of errors.
	 * @param array            $vars            The request variables.
	 *
	 * @return string $html The response with the HTML of the form, depending on the call.
	 */
	public function get_modal_content_ajax( $render_response, $vars ) {
		if ( 'tec_tickets_attendee_details' !== $vars['request'] ) {
			return $render_response;
		}

		$html = '';

		/** @var Tribe__Tickets__Editor__Template $template */
		$tickets_template = tribe( 'tickets.editor.template' );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_template = tribe( 'tickets.admin.views' );
		$attendee_id    = (int) sanitize_text_field( $vars['attendeeId'] );
		$provider       = tribe_tickets_get_ticket_provider( $attendee_id );

		if ( ! $provider ) {
			return '<div class="tec-tickets__admin-attendees-modal-attendee-info-value">' . esc_html__( 'Attendee provider not found.', 'event-tickets' ) . '</div>';
		}

		$attendee       = $provider->get_attendee( $attendee_id );
		$post_id        = (int) sanitize_text_field( $vars['eventId'] );
		$ticket_id      = (int) sanitize_text_field( $vars['ticketId'] );
		$attendee_name  = (string) sanitize_text_field( $vars['attendeeName'] );
		$attendee_email = (string) sanitize_text_field( $vars['attendeeEmail'] );

		// Send the attendee object to the template.
		$context = [
			'attendee'       => $attendee,
			'attendee_id'    => $attendee_id,
			'attendee_name'  => $attendee_name,
			'attendee_email' => $attendee_email,
			'post_id'        => $post_id,
			'ticket_id'      => $ticket_id,
			'qr_enabled'     => tribe( \TEC\Tickets\QR\Settings::class )->is_enabled( 'attendees-modal' ),
		];

		$html = $admin_template->template( 'attendees/modal/attendee', $context, false );

		$html .= $tickets_template->template( 'v2/components/loader/loader', [], false );

		return $html;
	}
}
