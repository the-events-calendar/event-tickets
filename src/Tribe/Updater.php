<?php


/**
 * Class Tribe__Tickets__Updater
 *
 * @since 4.7.1
 *
 */
class Tribe__Tickets__Updater extends Tribe__Events__Updater {

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
}
