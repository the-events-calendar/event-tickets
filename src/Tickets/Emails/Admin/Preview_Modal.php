<?php
namespace TEC\Tickets\Emails\Admin;

use TEC\Tickets\Emails\Assets as Assets;
use TEC\Tickets\Emails\Emails_Tab as Emails_Tab;
use TEC\Tickets\Emails\Email_Template as Email_Template;

/**
 * Class Preview_Modal
 *
 * @package TEC\Tickets\Emails
 *
 * @since TBD
 */
class Preview_Modal {

	/**
	 * Modal ID.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $modal_id = 'tec-tickets__emails-preview-dialog';

	/**
	 * Modal target.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public static $modal_target = 'tec-tickets__emails-preview-dialog';

	/**
	 * Check if we should render the modal.
	 *
	 * @since TBD
	 *
	 * @return boolean Whether we should render the modal.
	 */
	public function should_render(): bool {
		return tribe( Emails_Tab::class )->is_on_tab();
	}

	/**
	 * Render the `Emails` preview modal.
	 *
	 * @since TBD
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
	 * @since TBD
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
			// @todo @juanfra review `tribe-tickets__admin-container`
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
	 * @since TBD
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

		// @todo @juanfra: Check how's that we're gonna deal with the admin views
		// and see how we load wrappers here instead of hardcoding.
		$modal  = '<div class="tribe-common event-tickets">';
		$modal .= '<span id="' . esc_attr( static::$modal_target ) . '"></span>';
		$modal .= $modal_content;
		$modal .= '</div>';

		return $modal;
	}

	/**
	 * Get the default modal button args.
	 *
	 * @since TBD
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
				// @todo @juanfra: Review this one.
				'tribe-modal__title--emails-preview',
			],
		];

		return wp_parse_args( $args, $default_args );
	}

	/**
	 * Get the default modal button.
	 *
	 * @since TBD
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
	 * @since TBD
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

		$email_template = tribe( Email_Template::class );
		$email_template->set_preview( true );

		$html  = $email_template->get_html();
		$html .= $tickets_template->template( 'v2/components/loader/loader', [], false );

		return $html;
	}
}
