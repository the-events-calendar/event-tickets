<?php
/**
 * Handles hooking all the actions and filters used by the admin area.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Attendees;

/**
 * Class Page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

/**
 * Manages the admin settings UI in relation to ticket configuration.
 */
class Page {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets Attendees page slug.
	 *
	 * @var string
	 */
	public static $attendees_page_id = 'tec-tickets-attendees';

	/**
	 * Defines wether the current page is the Event Tickets Attendees page.
	 *
	 * @since TBD
	 *
	 * @return boolean
	 */
	public function is_tec_tickets_attendees(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$attendees_page_id === $admin_page;
	}

	/**
	 * Adds the Event Tickets Attendees page.
	 *
	 * @since TBD
	 */
	public function add_tec_tickets_attendees_page() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$attendees_page_id,
				'path'     => static::$attendees_page_id,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Attendees', 'event-tickets' ),
				'position' => 2.5,
				// 'capability' => 'manage_options',
				'callback' => [
					$this,
					'render_tec_tickets_attendees_page',
				],
			]
		);
	}

	/**
	 * Render the `Attendees` page.
	 *
	 * @since TBD.
	 *
	 * @return void
	 */
	public function render_tec_tickets_attendees_page() {
		tribe_asset_enqueue_group( 'event-tickets-admin-attendees' );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'attendees' => tribe( 'tickets.attendees' ),
			'event_id'  => 0,
		];

		echo '';
		//$admin_views->template( 'attendees', $context );
	}
}
