<?php
/**
 * Handles registering the admin menu and rendering of the All Tickets page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\All_Tickets;

use Tribe__Repository;

/**
 * Class Page.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Admin
 */
class Page {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets All Tickets page slug.
	 *
	 * @var string
	 */
	public static $slug = 'tec-tickets-all-tickets';

	/**
	 * Event Tickets All Tickets page hook suffix.
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-all-tickets';

	/**
	 * The provider filter query key.
	 *
	 * @var string
	 */
	const PROVIDER_KEY = 'provider-filter';

	/**
	 * The status filter query key.
	 *
	 * @var string
	 */
	const STATUS_KEY = 'status-filter';

	/**
	 * Get the ticket providers.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_provider_options() {
		/**
		 * Filters the ticket providers for the All Tickets Table.
		 *
		 * @since TBD
		 *
		 * @param array $providers The ticket providers for the All Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_all_tickets_table_provider_options', [] );
	}

	/**
	 * Get the ticket post types.
	 *
	 * @since TBD
	 *
	 * @return array
	 */
	public static function get_ticket_post_types() {
		$providers = static::get_provider_options();

		if ( empty( $providers ) ) {
			return [];
		}

		return array_keys( $providers );
	}

	/**
	 * Whether or not tickets exist to be displayed.
	 *
	 * @since TBD
	 *
	 * @return bool
	 */
	public static function tickets_exist() {
		$post_types = static::get_ticket_post_types();

		if ( empty( $post_types ) ) {
			return false;
		}

		/** @var Tribe__Repository $repository  */
		$repository = tribe_tickets()->by_args( [
			'post_type'      => static::get_ticket_post_types(),
		] );

		return $repository->found() > 0;
	}

	/**
	 * Defines wether the current page is the Event Tickets All Tickets page.
	 *
	 * @since TBD
	 *
	 * @return boolean
	 */
	public function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$slug === $admin_page;
	}

	/**
	 * Returns the main admin All Tickets URL.
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
		 * Filters the URL to the Event Tickets All Tickets page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Event Tickets All Tickets page.
		 */
		return apply_filters( 'tec_tickets_all_tickets_page_url', $url );
	}

	/**
	 * Adds the Event Tickets All Tickets page.
	 *
	 * @since TBD
	 */
	public function add_tec_tickets_all_tickets_page() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$slug,
				'path'     => static::$slug,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'All Tickets', 'event-tickets' ),
				'position' => 1.2,
				'callback' => [
					$this,
					'render_tec_tickets_all_tickets_page',
				],
			]
		);
	}

	/**
	 * Render the `All Tickets` page.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function render_tec_tickets_all_tickets_page() {
		tribe_asset_enqueue_group( 'event-tickets-admin-all-tickets' );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'tickets_table' => tribe( List_Table::class ),
			'page_slug'     => static::$slug,
			'tickets_exist' => ! static::tickets_exist(),
		];

		$admin_views->template( 'all-tickets', $context );
	}
}