<?php

use TEC\Tickets\Admin\Notice_New_Views_Upgrade;

/**
 * Class Tribe__Tickets__Updater
 *
 * @since 4.7.1
 * @since 4.10.2 - uses Tribe__Updater in common library instead of Tribe__Events__Tribe
 *
 */
class Tribe__Tickets__Updater extends Tribe__Updater {

	protected $version_option = 'event-tickets-schema-version';

	/**
	 * Force upgrade script to run even without an existing version number
	 * The version was not previously stored for Filter Bar
	 *
	 * @since 4.7.1
	 *
	 * @return bool
	 */
	public function is_new_install() {
		return false;
	}

	/**
	 * Returns an array of callbacks that should be called
	 * every time the version is updated.
	 *
	 * @since 4.12.0
	 * @since TBD Added the new views migration.
	 *
	 * @return array
	 */
	public function get_constant_update_callbacks() {
		return [
			[ $this, 'migrate_4_12_hide_attendees_list' ],
			[ $this, 'migrate_force_new_views' ],
		];
	}

	/**
	 * Schedules the new views migration for later in the request.
	 *
	 * This runs on every version bump rather than under a version key. A key at or below a version
	 * that already shipped never runs, because the site recorded that version when it got there, and
	 * a key above the shipping version never runs either, so the correct key is whatever release this
	 * turns out to go out in.
	 *
	 * The work itself waits for `wp_loaded`. Updates run on `init` at priority 0, before a theme or
	 * plugin filtering the views off has had a chance to register anything, so reading the flags
	 * there would record a value the site does not go on to render.
	 *
	 * @since TBD
	 */
	public function migrate_force_new_views() {
		add_action( 'wp_loaded', [ $this, 'force_new_views' ] );
	}

	/**
	 * Turns the new Tickets and RSVP views on for sites that still had them off.
	 *
	 * The settings that controlled these are gone and nothing in Event Tickets reads the options any
	 * more. They are written all the same, for anything outside the plugin that reads them directly.
	 *
	 * @since TBD
	 */
	public function force_new_views() {
		$enabled = [
			'tickets_use_new_views'      => tribe_tickets_new_views_is_enabled(),
			'tickets_rsvp_use_new_views' => tribe_tickets_rsvp_new_views_is_enabled(),
		];

		$switched = false;

		foreach ( $enabled as $option => $is_enabled ) {
			if ( $is_enabled && ! $this->was_rendering_new_views( $option ) ) {
				$switched = true;
			}

			/**
			* A site holding the views off through the constant, the env var or a filter stores the
			* value it actually renders, which is the whole point of still writing these.
			*/
			if ( $is_enabled !== tribe_get_option( $option ) ) {
				tribe_update_option( $option, $is_enabled );
			}
		}

		if ( ! $switched ) {
			return;
		}

		// This site was rendering the old views a moment ago, so it gets told.
		tribe_update_option( Notice_New_Views_Upgrade::OPTION_FORCED_ON, true );
	}

	/**
	 * Trigger setup of cron task to migrate the hide attendees list meta for block/shortcode enabled posts.
	 *
	 * @since 4.12.0
	 */
	public function migrate_4_12_hide_attendees_list() {
		/** @var \Tribe\Tickets\Migration\Queue_4_12 $migration */
		$migration = tribe( 'tickets.migration.queue_4_12' );

		// Trigger adding task to cron if it hasn't already been completed.
		if ( 'complete' !== $migration->get_current_offset() ) {
			$migration->register_scheduled_task();
		}
	}

	/**
	 * Whether the site was already on the new views before this upgrade reached it.
	 *
	 * The stored option is not enough on its own: a site that never opened the settings has no option
	 * at all, and what it rendered then depended on when it was first installed. This reproduces that
	 * answer, and it is the only thing left that needs to.
	 *
	 * @since TBD
	 *
	 * @param string $option The option to resolve.
	 *
	 * @return bool
	 */
	protected function was_rendering_new_views( $option ) {
		if ( 'tickets_rsvp_use_new_views' === $option ) {
			$default = ! tribe_installed_before( 'Tribe__Tickets__Main', '5.0' );
		} else {
			$default = ! tribe_installed_before( 'Tribe__Tickets__Main', '5.0.3' )
				|| ( class_exists( 'Tribe__Tickets_Plus__Main' ) && ! tribe_installed_before( 'Tribe__Tickets_Plus__Main', '5.1' ) );
		}

		return (bool) tribe_get_option( $option, $default );
	}
}
