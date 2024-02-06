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
	public static $slug = 'tec-tickets-attendees';

	/**
	 * Event Tickets Attendees page hook suffix.
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-attendees';

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

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}

	/**
	 * Returns the main admin attendees URL.
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$slug,
		];

		// Allow the link to be "changed" on the fly.
		$args = wp_parse_args( $args, $defaults );

		// Keep the resulting URL args clean.
		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		/**
		 * Filters the URL to the Event Tickets attendees page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Event Tickets attendees page.
		 */
		return apply_filters( 'tec_tickets_attendees_page_url', $url );
	}

	/**
	 * Adds the Event Tickets Attendees page.
	 *
	 * @since TBD
	 */
	public function add_tec_tickets_attendees_page() {
		$admin_pages = tribe( 'admin.pages' );

		$attendees_page = $admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Attendees', 'event-tickets' ),
				'position' => 1.5,
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

		$admin_views->template( 'attendees', $context );
	}
}
