<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets\Admin\Attendees\Hooks::class ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets\Admin\Attendees\Hooks::class ), 'some_method' ] );
 *
 * @since 5.9.1
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Class Hooks.
 *
 * @since 5.10.0
 *
 * @package TEC\Tickets\Admin
 */
class Hooks extends \tad_DI52_ServiceProvider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.10.0
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions for the Admin Attendees page.
	 *
	 * @since 5.10.0
	 */
	protected function add_actions() {
		add_action( 'admin_menu', tribe_callback( Page::class, 'add_tec_tickets_attendees_page' ), 15 );
		add_action( 'wp_ajax_tec_tickets_attendee_details', tribe_callback( Modal::class, 'display_attendee_details_modal' ) );
		add_action( 'admin_footer', tribe_callback( Modal::class, 'render_modal' ) );
	}

	/**
	 * Adds the filters for the Admin Attendees page.
	 *
	 * @since 5.10.0
	 */
	protected function add_filters() {
		add_filter( 'tribe_tickets_attendee_table_columns', tribe_callback( Page::class, 'filter_attendee_table_columns' ) );
		add_filter( 'tribe_events_tickets_attendees_table_column', tribe_callback( Page::class, 'render_column_attendee_event' ), 10, 3 );

		// Hook the `Attendees` preview for the AJAX requests.
		add_filter( 'tribe_tickets_admin_manager_request', tribe_callback( Modal::class, 'get_modal_content_ajax' ), 15, 2 );
	}
}
