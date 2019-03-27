<?php


/**
 * Class Tribe__Tickets__Updater
 *
 * @since 4.7.1
*  @since 4.10.2 - uses Tribe__Updater in common library instead of Tribe__Events__Tribe
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
}
