<?php


/**
 * Class Tribe__Tickets__Admin__Views
 *
 * Hooks view links handler for supported post types edit pages.
 *
 * "Views" are the links on top of a WordPress admin post list.
 * This class does not contain the business logic, it only hooks the classes
 * that will handle the logic.
 *
 * @link https://make.wordpress.org/docs/plugin-developer-handbook/10-plugin-components/custom-list-table-columns/#views
 */
class Tribe__Tickets__Admin__Views {

	/**
	 * @var Tribe__Tickets__Admin__Views__Ticketed
	 */
	protected $ticketed;

	/**
	 * Adds the view links on supported post types admin  lists.
	 *
	 * @param array $supported_types A list of the post types that can have tickets.
	 */
	public function add_view_links( array $supported_types = array() ) {
		if ( empty( $supported_types ) ) {
			return true;
		}

		foreach ( $supported_types as $supported_type ) {
			$ticketed_view = new Tribe__Tickets__Admin__Views__Ticketed( $supported_type );
			add_filter( 'views_edit-' . $supported_type, array( $ticketed_view, 'filter_edit_link' ) );
		}

		return true;
	}

	public function hook() {
		$this->add_view_links( (array) tribe_get_option( 'ticket-enabled-post-types', array() ) );
	}
}
