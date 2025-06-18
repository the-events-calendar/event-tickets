<?php

namespace TEC\Tickets\Emails\Admin;

/**
 * Class Notice_Upgrade
 *
 * @since 5.6.0
 *
 * @package TEC\Tickets\Emails\Admin
 */
class Notice_Upgrade {
	/**
	 * Register upgrade notice for Tickets Emails.
	 *
	 * @since 5.6.0
	 */
	public function hook(): void {
		tribe_notice(
			'tickets-emails-upgrade',
			[ $this, 'notice' ],
			[
				'dismiss' => 1,
				'type'    => 'info',
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
	protected function is_preexisting_install(): bool {
		return tribe_installed_before( \Tribe__Tickets__Main::class, '5.6.0' );
	}

	/**
	 * Checks all methods required for display.
	 *
	 * @since 5.6.0
	 *
	 * @return bool
	 */
	public function should_display(): bool {
		return $this->is_valid_screen() && $this->is_preexisting_install() && ! tec_tickets_emails_is_enabled();
	}

	/**
	 * HTML for the notice for sites using V1.
	 *
	 * @since 5.6.0
	 *
	 * @return string
	 */
	public function notice(): string {
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/event-tickets-emails' ),
			esc_html_x( 'Learn More', 'Upgrade notice for Tickets Emails.', 'event-tickets' )
		);

		return '<strong>' . esc_html__( 'A new way to manage your emails', 'event-tickets' ) . '</strong>' .
		       '<br />' .
		       sprintf(
			       _x( 'There\'s a new way to manage your Event Tickets related emails. Enable the functionality, configure the look and feel, and much more from the Emails tab. Customizations in old templates won\'t have an effect when using the new emails, feel free to update when you are ready. %1$s.', 'upgrade notice for tickets emails', 'event-tickets' ),
			       $link
		       );
	}
}
