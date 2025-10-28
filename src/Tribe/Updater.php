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
	 *
	 * @return array
	 */
	public function get_constant_update_callbacks() {
		return [
			[ $this, 'migrate_4_12_hide_attendees_list' ],
			[ $this, 'ensure_tickets_commerce_pages_exist' ],
		];
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
	 * Ensure Tickets Commerce checkout and success pages exist.
	 *
	 * Runs on every plugin update/load to ensure pages are created for both
	 * fresh installs and existing sites where Tickets Commerce is now always enabled.
	 *
	 * @since TBD
	 */
	public function ensure_tickets_commerce_pages_exist() {
		tribe( \TEC\Tickets\Commerce\Payments_Tab::class )->maybe_generate_pages();
	}

}
