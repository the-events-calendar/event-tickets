<?php
/**
 * Tells a site that was switched to the updated ticket and RSVP views what changed.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin;

/**
 * Class Notice_New_Views_Upgrade
 *
 * The switch happens without the site asking for it and takes their template overrides out of use
 * with it, and nothing else says so: Tribe__Template stops looking for an override rather than
 * reporting that it found one it can no longer use.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Admin
 */
class Notice_New_Views_Upgrade {

	/**
	 * Option recording that the upgrade turned the new views on for this site.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const OPTION_FORCED_ON = 'tec_tickets_new_views_forced_on';

	/**
	 * Registers the notice.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function hook(): void {
		tribe_notice(
			'tickets-new-views-upgrade',
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
	 * Whether this site is one the upgrade switched over.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public function should_display(): bool {
		if ( ! tribe_get_option( self::OPTION_FORCED_ON ) ) {
			return false;
		}

		/** @var \Tribe__Settings $settings */
		$settings = tribe( 'settings' );

		// Bail if user cannot change settings.
		if ( ! current_user_can( $settings->requiredCap ) ) {
			return false;
		}

		/** @var \Tribe__Admin__Helpers $admin_helpers */
		$admin_helpers = tribe( 'admin.helpers' );

		return $admin_helpers->is_screen() || $admin_helpers->is_post_type_screen();
	}

	/**
	 * The notice body.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function notice(): string {
		$link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url( 'https://evnt.is/1an-' ),
			esc_html_x( 'Learn More', 'Upgrade notice for the updated ticket views.', 'event-tickets' )
		);

		return '<strong>' . esc_html(
			sprintf(
				/* translators: %1$s: dynamic "Ticket" text. */
				_x( 'Your %1$s and RSVP forms have been updated', 'upgrade notice heading', 'event-tickets' ),
				tribe_get_ticket_label_singular( 'new_views_upgrade_notice' )
			)
		) . '</strong>' .
			'<br />' .
			sprintf(
				/* translators: %1$s: link to the documentation. */
				esc_html_x( 'The setting that kept this site on the older front-end views has been removed, and the updated experience is now always on. Custom templates and CSS written against the older markup no longer apply. %1$s', 'upgrade notice for the updated ticket views', 'event-tickets' ),
				$link
			);
	}
}
