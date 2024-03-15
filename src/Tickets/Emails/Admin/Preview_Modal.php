<?php
/**
 * Handles the Email preview modal.
 *
 * @since 5.5.7
 *
 * @package TEC\Tickets\Emails
 */

namespace TEC\Tickets\Emails\Admin;

use TEC\Tickets\Emails\Email_Handler;
use Tribe__Utils__Array as Arr;
use TEC\Tickets\Emails\Assets;
use TEC\Tickets\Emails\Email\Ticket;

/**
 * Class Preview_Modal
 *
 * @since 5.5.7
 * @package TEC\Tickets\Emails
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

		// phpcs:ignore
		echo $this->get_modal_content();
	}

	/**
	 * Get the default modal args.
	 *
	 * @since 5.5.7
	 *
	 * @return array The default modal args.
	 */
	public function get_modal_args(): array {
		return [
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
	}

	/**
	 * Get the default modal contents.
	 *
	 * @since 5.5.7
	 *
	 * @return string The modal content.
	 */
	public function get_modal_content(): string {
		/** @var \Tribe__Tickets__Editor__Template $template */
		$template = tribe( 'tickets.editor.template' );

		$content = $template->template( 'v2/components/loader/loader', [], false );

		$args = $this->get_modal_args();

		$modal  = '<div class="tribe-common event-tickets">';
		$modal .= '<span id="' . esc_attr( static::$modal_target ) . '"></span>';
		$modal .= tribe( 'dialog.view' )->render_modal( $content, $args, static::$modal_id, false );
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
		return $dialog_view->template( 'button', $args, false );
	}

	/**
	 * Get the `Tickets Emails` preview modal content,
	 * depending on the request.
	 *
	 * @since 5.5.7
	 *
	 * @todo This method should not be handling variables from other Add-ons but it currently does.
	 *
	 * @param string|\WP_Error $render_response The render response HTML content or WP_Error with list of errors.
	 * @param array            $vars            The request variables.
	 *
	 * @return string $html The response with the HTML of the form, depending on the call.
	 */
	public function get_modal_content_ajax( $render_response, $vars ) {
		/** @var \Tribe__Tickets__Editor__Template $template */
		$tickets_template = tribe( 'tickets.editor.template' );

		$preview_context = [
			'is_preview' => true,
		];

		$ticket_bg_color = Arr::get( $vars, 'ticketBgColor', '' );

		if ( ! empty( $ticket_bg_color ) ) {
			$preview_context['ticket_bg_color']   = wp_kses( $ticket_bg_color, [] );
			$preview_context['ticket_text_color'] = \Tribe__Utils__Color::get_contrast_color( $preview_context['ticket_bg_color'] );
		}

		$footer_content = Arr::get( $vars, 'footerContent', '' );

		if ( ! empty( $footer_content ) ) {
			$preview_context['footer_content'] = wp_kses_post( $footer_content );
		}

		$footer_credit = Arr::get( $vars, 'footerCredit', '' );

		if ( ! empty( $footer_credit ) ) {
			$preview_context['footer_credit'] = tribe_is_truthy( $footer_credit );
		}

		$header_bg_color = Arr::get( $vars, 'headerBgColor', '' );

		if ( ! empty( $header_bg_color ) ) {
			$preview_context['header_bg_color']   = wp_kses( $header_bg_color, [] );
			$preview_context['header_text_color'] = \Tribe__Utils__Color::get_contrast_color( $preview_context['header_bg_color'] );
		}

		$header_img_url = Arr::get( $vars, 'headerImageUrl', '' );

		if ( ! empty( $header_img_url ) ) {
			$preview_context['header_image_url'] = esc_url( $header_img_url );
		}

		$header_image_alignment = Arr::get( $vars, 'headerImageAlignment', '' );

		if ( ! empty( $header_image_alignment ) ) {
			$preview_context['header_image_alignment'] = sanitize_key( strtolower( $header_image_alignment ) );
		}

		$rsvp_using_ticket_email               = tribe_is_truthy( Arr::get( $vars, 'useTicketEmail', '' ) );
		$preview_context['using_ticket_email'] = $rsvp_using_ticket_email;

		// Only apply JS preview context if we're not using the ticket email.
		if ( ! $rsvp_using_ticket_email ) {
			$heading = Arr::get( $vars, 'heading', '' );

			if ( ! empty( $heading ) ) {
				$preview_context['heading'] = sanitize_text_field( stripslashes( $heading ) );
			}

			$additional_content = Arr::get( $vars, 'addContent', '' );

			if ( ! empty( $additional_content ) ) {
				$preview_context['additional_content'] = wp_unslash( wp_kses_post( $additional_content ) );
			}

			$add_qr_codes = Arr::get( $vars, 'includeQrCodes', '' );

			if ( ! empty( $add_qr_codes ) ) {
				$preview_context['add_qr_codes'] = tribe_is_truthy( $add_qr_codes );
			}

			$preview_context['add_event_links'] = tribe_is_truthy( Arr::get( $vars, 'addEventLinks', '' ) );
			$preview_context['add_ar_fields']   = tribe_is_truthy( Arr::get( $vars, 'includeArFields', '' ) );
		}

		$current_email = Arr::get( $vars, 'currentEmail', '' );
		$email_class   = null;

		// Select email class to preview, if not using ticket email.
		if ( ! $rsvp_using_ticket_email && ! empty( $current_email ) ) {
			$email = tribe( Email_Handler::class )->get_email_by_id( $current_email );
			if ( ! empty( $email ) ) {
				$email_class = tribe( get_class( $email ) );
			}
		}

		if ( null === $email_class ) {
			// Show Ticket email by default.
			$email_class = tribe( Ticket::class );
		}

		$email_class->set_placeholders( Preview_Data::get_placeholders() );
		$email_preview_context = $email_class->get_preview_context( $preview_context );

		foreach ( $email_preview_context as $key => $template_var_value ) {
			$email_class->set( $key, $template_var_value );
		}

		if ( ! isset( $email_preview_context['post_id'] ) ) {
			$email_class->set( 'post_id', Preview_Data::get_post()->ID );
		}

		$preview_for_event = isset( $email_preview_context['post'] )
							&& is_object( $email_preview_context['post'] )
							&& ( $email_preview_context['post']->post_type ?? 'tribe_events' ) === 'tribe_events';

		if ( $preview_for_event ) {
			add_filter( 'tribe_is_event', '__return_true' );
		}

		$html = $email_class->get_content();

		if ( $preview_for_event ) {
			remove_filter( 'tribe_is_event', '__return_true' );
		}

		return $html . $tickets_template->template( 'v2/components/loader/loader', [], false );
	}
}
