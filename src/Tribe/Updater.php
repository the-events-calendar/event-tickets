<?php

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
	 * Turns the new Tickets and RSVP views on for sites that still had them off.
	 *
	 * The settings that controlled these are gone and nothing in Event Tickets reads the options any
	 * more. They are written all the same, for anything outside the plugin that reads them directly.
	 *
	 * This runs on every version bump rather than under a version key. A key at or below a version
	 * that already shipped never runs, because the site recorded that version when it got there, and
	 * a key above the shipping version never runs either, so the correct key is whatever release this
	 * turns out to go out in. Writing an option that is already true costs nothing.
	 *
	 * @since TBD
	 */
	public function migrate_force_new_views() {
		$resolved = [
			'tickets_use_new_views'      => tribe_tickets_new_views_is_enabled(),
			'tickets_rsvp_use_new_views' => tribe_tickets_rsvp_new_views_is_enabled(),
		];

		foreach ( $resolved as $option => $enabled ) {
			// A site holding the views off through the constant, the env var or a filter keeps a
			// stored value that agrees with what it actually renders.
			if ( ! $enabled || true === tribe_get_option( $option ) ) {
				continue;
			}

			tribe_update_option( $option, true );
		}
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

}
