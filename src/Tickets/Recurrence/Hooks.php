<?php

namespace TEC\Tickets\Recurrence;

class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * @since 5.5.0
	 */
	public function add_filters() {
		add_filter( 'tribe_tickets_settings_post_type_ignore_list', [ $this, 'disallow_attaching_tickets_to_series' ] );
	}

	/**
	 * @since 5.5.0
	 */
	public function add_actions() {
	}

	/**
	 * Attaching tickets to objects of the Series CPT is unsupported at the moment and will not work properly.
	 * Removes the option from the Settings > Tickets page.
	 *
	 * @since 5.5.0
	 *
	 * @param array $post_types A list of restricted post types.
	 *
	 * @return array
	 */
	public function disallow_attaching_tickets_to_series( $post_types ) {
		return array_merge( $post_types, Compatibility::get_restricted_post_types() );
	}
}