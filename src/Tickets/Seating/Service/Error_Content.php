<?php
/**
 * Handles the rendering of the service error content.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Service;
 */

namespace TEC\Tickets\Seating\Service;

use TEC\Tickets\Seating\Admin\Template;

/**
 * Class Error_Content.
 *
 * @since   5.16.0
 *
 * @package TEC\Tickets\Seating\Service;
 */
class Error_Content {
	/**
	 * A reference to the Service Status object.
	 *
	 * @since 5.16.0
	 *
	 * @var Service_Status
	 */
	private Service_Status $status;

	/**
	 * A reference to the Template object.
	 *
	 * @since 5.16.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Error_Content constructor.
	 *
	 * @since 5.16.0
	 *
	 * @param Template $template A reference to the admin Template object.
	 */
	public function __construct( Template $template ) {
		$this->template = $template;
	}

	/**
	 * Renders the error content in the context of an admin area tab.
	 *
	 * @since 5.16.0
	 *
	 * @param Service_Status $status The service status object.
	 */
	public function render_tab( Service_Status $status ): void {
		$cta_url   = null;
		$cta_label = null;

		switch ( $status->get_status() ) {
			case Service_Status::NO_LICENSE:
				// upsell ?
				return;
			case Service_Status::SERVICE_UNREACHABLE:
				$message = __(
					'Your site cannot connect to the Seating Builder service.',
					'event-tickets'
				);
				break;
			case Service_Status::NOT_CONNECTED:
				$message   = __(
					'You need to connect your site to the Seating Builder in order to create Seating Maps and Seat Layouts.',
					'event-tickets'
				);
				$cta_label = _x( 'Connect', 'Connect to the Seating Builder button label', 'event-tickets' );
				$cta_url   = admin_url( 'admin.php?page=tec-tickets-settings&tab=licenses' );
				break;
			case Service_Status::EXPIRED_LICENSE:
				$renew_link = sprintf(
				// translators: %s is the renew link label.
					'<a href="https://evnt.is/1bdu" target="_blank" rel="noreferrer noopener">%s</a>',
					_x( 'renew your license', 'link label for renewing the license', 'event-tickets' )
				);
				$message = sprintf(
				// translators: %s is the renew link.
					__(
						'Your license for Seating has expired. You need to %s to continue using Seating for Event Tickets.',
						'event-tickets'
					),
					$renew_link
				);
				break;
			case Service_Status::INVALID_LICENSE:
				$settings_link = sprintf(
				// translators: %s is the settings link label.
					'<a href="https://evnt.is/1bdu" target="_blank" rel="noreferrer noopener">%s</a>',
					_x( 'Check your license key settings', 'License settings link label', 'event-tickets' )
				);
				
				$login_link = sprintf(
				// translators: %s is the login link label.
					'<a href="https://evnt.is/1be1 " target="_blank" rel="noreferrer noopener">%s</a>',
					_x( 'log into your account', 'Login link label', 'event-tickets' )
				);
				
				$message = sprintf(
				// translators: %1$s is the settings link, %2$s is the login link.
					__(
						'Your license for Seating is invalid. %1$s or %2$s.',
						'event-tickets'
					),
					$settings_link,
					$login_link
				);
				break;
			default:
		}

		$this->template->template(
			'service-error-tab',
			[
				'message'   => $message,
				'cta_label' => $cta_label,
				'cta_url'   => $cta_url,
			]
		);
	}

	/**
	 * Returns the message to be displayed in the editor relative to the service status.
	 *
	 * @since 5.16.0
	 *
	 * @param Service_Status $status The service status object.
	 *
	 * @return string The message to be displayed in the editor.
	 */
	public function get_editor_message( Service_Status $status ): string {
		$message = '';

		switch ( $status->get_status() ) {
			default:
				break;
			case Service_Status::SERVICE_UNREACHABLE:
				$message = __(
					'Your site cannot connect to the Seating Builder service and assigned seating is not available.',
					'event-tickets'
				);
				break;
			case Service_Status::NOT_CONNECTED:
				$connect_link_url = admin_url( 'admin.php?page=tec-tickets-settings&tab=licenses' );
				$connect_link     = sprintf(
					// translators: %1$s is the connect link, %2$s is the connect link label.
					'<a href="%1$s" target="_blank">%2$s</a>',
					$connect_link_url,
					_x( 'Connect', 'Connect to the Seating Builder link label', 'event-tickets' )
				);
				$message = sprintf(
					// translators: %s is the connect link.
					__(
						'Your site is not connected to the Seating Builder service. You need to connect your site to use assigned seating. %s',
						'event-tickets'
					),
					$connect_link
				);
				break;
			case Service_Status::EXPIRED_LICENSE:
			case Service_Status::INVALID_LICENSE:
				$renew_link = sprintf(
					// translators: %s is the renew link label.
					'<a href="https://evnt.is/1bdu" target="_blank" rel="noreferrer noopener">%s</a>',
					_x( 'Renew your license', 'link label for renewing the license', 'event-tickets' )
				);
				$message = sprintf(
					// translators: %s is the renew link.
					__(
						'Your license for Seating has expired. %s to continue using Seating for Event Tickets.',
						'event-tickets'
					),
					$renew_link
				);
				break;
		}

		return wp_kses(
			$message,
			[
				'a' => [
					'href'   => [],
					'target' => [],
					'rel'    => [],
				],
			]
		);
	}
}
