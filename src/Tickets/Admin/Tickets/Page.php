<?php
/**
 * Handles registering the admin menu and rendering of the All Tickets page.
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use Tribe__Events__Main;
use Tribe__Repository;
use Tribe__Tickets__Main;

/**
 * Class Page.
 *
 * @since 5.14.0
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
	public static $slug = 'tec-tickets-admin-tickets';

	/**
	 * Event Tickets All Tickets page hook suffix.
	 *
	 * @var string
	 */
	public static $hook_suffix = 'tickets_page_tec-tickets-admin-tickets';

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
	 * Get Provider information.
	 *
	 * @since 5.14.0
	 *
	 * @return array
	 */
	public static function get_provider_info() {
		/**
		 * Filters the ticket providers for the All Tickets Table.
		 *
		 * @since 5.14.0
		 *
		 * @param array $providers The ticket providers for the All Tickets Table.
		 *
		 * @return array
		 */
		return apply_filters( 'tec_tickets_admin_tickets_table_provider_info', [] );
	}

	/**
	 * Get the ticket providers.
	 *
	 * @since 5.14.0
	 *
	 * @return array
	 */
	public static function get_provider_options() {
		$providers        = static::get_provider_info();
		$provider_options = [];

		foreach ( $providers as $provider => $provider_info ) {
			if ( empty( $provider_info['title'] ) ) {
				continue;
			}
			$provider_options[ $provider ] = $provider_info['title'];
		}

		return $provider_options;
	}

	/**
	 * Get the currently selected provider.
	 *
	 * @since 5.14.0
	 *
	 * @return string;
	 */
	public static function get_current_provider() {
		$provider_info    = static::get_provider_info();
		$default_provider = empty( $provider_info ) ? '' : addslashes( key( $provider_info ) );
		$current_provider = tribe_get_request_var( static::PROVIDER_KEY, $default_provider );

		return stripslashes( $current_provider );
	}

	/**
	 * Get the currently selected provider object.
	 *
	 * @since 5.14.0
	 *
	 * @return Tribe__Tickets__Tickets|null;
	 */
	public static function get_current_provider_object() {
		$current_provider = static::get_current_provider();

		return tribe_get_class_instance( $current_provider );
	}

	/**
	 * Get the currently selected ticket post type.
	 *
	 * @since 5.14.0
	 *
	 * @return string|null;
	 */
	public static function get_current_post_type() {
		$selected_provider = static::get_current_provider();

		if ( empty( $selected_provider ) ) {
			return null;
		}

		$post_types = static::get_ticket_post_types();

		return $post_types[ $selected_provider ] ?? null;
	}

	/**
	 * Get the ticket post types.
	 *
	 * @since 5.14.0
	 *
	 * @return array
	 */
	public static function get_ticket_post_types() {
		$provider_info = static::get_provider_info();
		$post_types    = [];

		foreach ( $provider_info as $provider => $provider_info ) {
			if ( empty( $provider_info['ticket_post_type'] ) ) {
				continue;
			}
			$post_types[ $provider ] = $provider_info['ticket_post_type'];
		}

		return $post_types;
	}

	/**
	 * Whether or not tickets exist to be displayed.
	 *
	 * @since 5.14.0
	 *
	 * @return bool
	 */
	public static function tickets_exist() {
		$post_types = static::get_ticket_post_types();

		if ( empty( $post_types ) ) {
			return false;
		}

		/** @var Tribe__Repository $repository  */
		$repository = tribe_tickets()->by_args(
			[
				'post_type' => static::get_ticket_post_types(),
			]
		);

		return $repository->found() > 0;
	}

	/**
	 * Defines wether the current page is the Event Tickets All Tickets page.
	 *
	 * @since 5.14.0
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
		 * @since 5.14.0
		 *
		 * @param string $url The URL to the Event Tickets All Tickets page.
		 */
		return apply_filters( 'tec_tickets_admin_tickets_page_url', $url );
	}

	/**
	 * Adds the Event Tickets All Tickets page.
	 *
	 * @since 5.14.0
	 */
	public function add_tec_tickets_admin_tickets_page() {
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
					'render_tec_tickets_admin_tickets_page',
				],
			]
		);
	}

	/**
	 * Get the link to edit posts.
	 *
	 * @since 5.14.0
	 *
	 * @return string
	 */
	public function get_link_to_edit_posts() {
		// Get array of enabled post types.
		$post_types = Tribe__Tickets__Main::instance()->post_types();
		$not_set    = empty( $post_types );
		$has_tec    = did_action( 'tribe_events_bound_implementations' );

		if ( $has_tec && ( in_array( 'tribe_events', $post_types, true ) || $not_set ) ) {
			// If TEC is installed and the event post type is enabled or post types are not set, return the event post type.
			$post_type = Tribe__Events__Main::POSTTYPE;
		} elseif ( in_array( 'page', $post_types, true ) || empty( $post_types ) ) {
			// If the page post type is enabled or post types are not set, return the page post type.
			$post_type = 'page';
		} else {
			// Otherwise, return the first post type in the array.
			$post_type = $post_types[0];
		}

		// Create link to edit posts page.
		return add_query_arg( [ 'post_type' => $post_type ], admin_url( 'edit.php' ) );
	}

	/**
	 * Render the `All Tickets` page.
	 *
	 * @since 5.14.0
	 *
	 * @return void
	 */
	public function render_tec_tickets_admin_tickets_page() {
		tribe_asset_enqueue_group( 'event-tickets-admin-tickets' );

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$context = [
			'tickets_table'  => tribe( List_Table::class ),
			'page_slug'      => static::$slug,
			'tickets_exist'  => static::tickets_exist(),
			'edit_posts_url' => $this->get_link_to_edit_posts(),
		];

		$admin_views->template( 'admin-tickets', $context );
	}
}
