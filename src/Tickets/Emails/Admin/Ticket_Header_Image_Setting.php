<?php
/**
 * Hides the per-event Ticket Header Image setting when Tickets Emails is enabled.
 *
 * @since 5.31.1
 *
 * @package TEC\Tickets\Emails\Admin
 */

namespace TEC\Tickets\Emails\Admin;

/**
 * Class Ticket_Header_Image_Setting.
 *
 * Tickets Emails reads the header image only from the Emails settings tab, so the per-event
 * image is used by the legacy email template alone.
 *
 * @since 5.31.1
 *
 * @package TEC\Tickets\Emails\Admin
 */
final class Ticket_Header_Image_Setting {
	/**
	 * Prevents the classic editor header image setting from rendering when Tickets Emails is enabled.
	 *
	 * @since 5.31.1
	 *
	 * @param null|string $html The initial HTML.
	 *
	 * @return null|string|false The initial HTML, or `false` to prevent the template from rendering.
	 */
	public function hide_classic_editor_setting( $html ) {
		return tec_tickets_emails_is_enabled() ? false : $html;
	}

	/**
	 * Tells the block editor whether to show the header image setting.
	 *
	 * @since 5.31.1
	 *
	 * @param array{tickets?: array<string,mixed>} $config The editor configuration.
	 *
	 * @return array{tickets: array<string,mixed>} The editor configuration with the `ticketHeaderImageEnabled` flag.
	 */
	public function add_block_editor_config( array $config ): array {
		$config['tickets'] ??= [];

		$config['tickets']['ticketHeaderImageEnabled'] = ! tec_tickets_emails_is_enabled();

		return $config;
	}
}
