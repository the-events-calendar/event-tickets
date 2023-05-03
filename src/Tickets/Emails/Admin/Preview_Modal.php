<?php
namespace TEC\Tickets\Emails\Admin;

use Tribe__Utils__Array as Arr;
use TEC\Tickets\Emails\Assets as Assets;
use TEC\Tickets\Emails\Admin\Emails_Tab as Emails_Tab;
use TEC\Tickets\Emails\Email\Ticket;
use TEC\Tickets\Emails\Email_Template as Email_Template;

/**
 * Class Preview_Modal
 *
 * @package TEC\Tickets\Emails
 *
 * @since 5.5.7
 */
class Preview_Modal {

	/**
	 * Modal ID.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $modal_id = 'tec-tickets__emails-preview-dialog';

	/**
	 * Modal target.
	 *
	 * @since 5.5.7
	 *
	 * @var string
	 */
	public static $modal_target = 'tec-tickets__emails-preview-dialog';

	/**
	 * Check if we should render the modal.
	 *
	 * @since 5.5.7
	 *
	 * @return boolean Whether we should render the modal.
	 */
	public function should_render(): bool {
		return tribe( Emails_Tab::class )->is_on_tab();
	}

	/**
	 * Render the `Emails` preview modal.
	 *
	 * @since 5.5.7
	 */
	public function render_modal() {
		if ( ! $this->should_render() ) {
			return;
		}

		// Enqueue `Emails` assets.
		tribe_asset_enqueue_group( Assets::$group_key );

		tribe_asset_enqueue_group( 'tribe-tickets-admin' );

		// Render the modal contents.
		echo $this->get_modal_content();
	}

	/**
	 * Get the default modal args.
	 *
	 * @since 5.5.7
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return array The default modal args.
	 */
	public function get_modal_args( $args = [] ): array {
		$default_args = [
			'append_target'           => '#' . static::$modal_target,
			'button_display'          => false,
			'close_event'             => 'tribeDialogCloseEmailsPreviewModal.tribeTickets',
			'show_event'              => 'tribeDialogShowEmailsPreviewModal.tribeTickets',
			'content_wrapper_classes' => 'tribe-dialog__wrapper tribe-modal__wrapper--emails-preview tribe-tickets__admin-container event-tickets tribe-common',
			'title'                   => esc_html__( 'Email Preview', 'event-tickets' ),
			'title_classes'           => [
				'tribe-dialog__title',
				'tribe-modal__title',
				'tribe-common-h5',
				'tribe-modal__title--emails-preview',
			],
		];

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Get the default modal contents.
	 *
	 * @since 5.5.7
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
	 * @since 5.5.7
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return array The default modal button args.
	 */
	public static function get_modal_button_args( $args = [] ): array {
		$default_args = [
			'id'                      => static::$modal_id,
			'append_target'           => '#' . static::$modal_target,
			'button_classes'          => [ 'button', 'action', 'button-primary', 'tec-tickets__admin-settings-emails-preview-button' ],
			'button_attributes'       => [ 'data-modal-title' => esc_html__( 'Preview Email', 'event-tickets' ) ],
			'button_display'          => true,
			'button_id'               => 'tec-tickets__admin-emails-preview-' . uniqid(),
			'button_name'             => 'tec-tickets-emails-preview',
			'button_text'             => esc_attr_x( 'Preview Email', 'Preview email button on the settings', 'event-tickets' ),
			'button_type'             => 'button',
			'close_event'             => 'tribeDialogCloseEmailsPreviewModal.tribeTickets',
			'show_event'              => 'tribeDialogShowEmailsPreviewModal.tribeTickets',
			'content_wrapper_classes' => 'tribe-dialog__wrapper event-tickets tribe-common',
			'title'                   => esc_html__( 'Preview Email', 'event-tickets' ),
			'title_classes'           => [
				'tribe-dialog__title',
				'tribe-modal__title',
				'tribe-common-h5',
				'tribe-modal__title--emails-preview',
			],
		];

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Get the default modal button.
	 *
	 * @since 5.5.7
	 *
	 * @param array $args Override default args by sending them in the `$args`.
	 *
	 * @return string The modal button.
	 */
	public static function get_modal_button( $args = [] ): string {
		$args        = self::get_modal_button_args( $args );
		$dialog_view = tribe( 'dialog.view' );
		$button      = $dialog_view->template( 'button', $args, false );

		return $button;
	}

	/**
	 * Get the `Tickets Emails` preview modal content,
	 * depending on the request.
	 *
	 * @since 5.5.7
	 *
	 * @param string|\WP_Error $render_response The render response HTML content or WP_Error with list of errors.
	 * @param array            $vars            The request variables.
	 *
	 * @return string $html The response with the HTML of the form, depending on the call.
	 */
	public function get_modal_content_ajax( $render_response, $vars ) {
		$html = '';

		/** @var Tribe__Tickets__Editor__Template $template */
		$tickets_template = tribe( 'tickets.editor.template' );

		$context = [
			'is_preview' => true,
		];

		$ticket_bg_color = Arr::get( $vars, 'ticketBgColor', '' );

		if ( ! empty( $ticket_bg_color ) ) {
			$context['ticket_bg_color'] = $ticket_bg_color;
		}

		$header_bg_color = Arr::get( $vars, 'headerBgColor', '' );

		if ( ! empty( $header_bg_color ) ) {
			$context['header_bg_color'] = $header_bg_color;
		}

		$header_img_url = Arr::get( $vars, 'headerImageUrl', '' );

		if ( ! empty( $header_img_url ) ) {
			$context['header_image_url'] = $header_img_url;
		}

		$current_email = Arr::get( $vars, 'currentEmail', '' );

		$html = '';

		if ( ! empty( $current_email ) ) {
			$email = tribe( \TEC\Tickets\Emails\Email_Handler::class )->get_email_by_id( $current_email );

			if ( ! empty( $email ) ) {
				$email_class = tribe( get_class( $email ) );
				$email_class->set_placeholders( Preview_Data::get_placeholders() );
				$html        = $email_class->get_content( $email_class->get_preview_context() );
			}

		} else {
			// Show Ticket email by default.
			$email_class = tribe( Ticket::class );
			$email_class->set_placeholders( Preview_Data::get_placeholders() );
			$html        = $email_class->get_content( $email_class->get_preview_context() );
		}

		$html .= $tickets_template->template( 'v2/components/loader/loader', [], false );

		return $html;
	}
}
