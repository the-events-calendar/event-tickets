<?php

namespace TEC\Tickets\Emails\Admin;

/**
 * Class Notice_Extension
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails\Admin
 */
class Notice_Extension {
	/**
	 * Register upgrade notice for Tickets Emails.
	 *
	 * @since 5.6.0
	 */
	public function hook(): void {
		tribe_notice(
			'tickets-emails-has-extension',
			[ $this, 'notice' ],
			[
				'dismiss' => 1,
				'type'    => 'warning',
				'wrap'    => 'p',
			],
			[ $this, 'should_display' ]
		);
	}

	/**
	 * Checks if we are in a page we need to display.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	protected function is_valid_screen(): bool {
		/** @var \Tribe__Admin__Helpers $admin_helpers */
		$admin_helpers = tribe( 'admin.helpers' );

		return $admin_helpers->is_screen() || $admin_helpers->is_post_type_screen();
	}

	/**
	 * Check if it's a preexisting installation.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	protected function is_extension_active(): bool {
		return class_exists( '\Tribe__Extension__Ticket_Email_Settings' );
	}

	/**
	 * Checks all methods required for display.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	public function should_display(): bool {
		return $this->is_valid_screen() && $this->is_extension_active() && tec_tickets_emails_is_enabled();
	}

	/**
	 * HTML for the notice for sites using the Event Tickets Email Settings extension.
	 *
	 * @since 5.6.0
	 *
	 * @return string
	 */
	public function notice(): string {
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			'https://evnt.is/1arx',
			esc_html_x( 'Learn More', 'Extension notice for Tickets Emails.', 'event-tickets' )
		);

		return '<strong>' . esc_html__( 'Ticket Email Settings extension is no longer supported', 'event-tickets' ) . '</strong>' .
		       '<br />' .
		       sprintf(
			       _x( 'We noticed you have the Ticket Email Settings extension active. The settings from the extension, living in the "Ticket Emails" tab, will no longer affect email functionality, and the extension will no longer be supported. Please be sure you configure your emails from the "Emails" tab. %1$s.', 'extension usage notice for tickets emails', 'event-tickets' ),
			       $link
		       );
	}
}
