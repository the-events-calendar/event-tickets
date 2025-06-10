<?php
/**
 * Handles registering the admin menu and rendering of the All Tickets page.
 *
 * @since 5.14.0
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\Tickets;

use TEC\Common\Admin\Abstract_Admin_Page;
use TEC\Common\Admin\Traits\Tabbed_View;
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
class Page extends Abstract_Admin_Page {
	use Tabbed_View;

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static string $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets All Tickets page slug.
	 *
	 * @var string
	 */
	public static string $page_slug = 'tec-tickets-admin-tickets';

	/**
	 * Event Tickets All Tickets page hook suffix.
	 *
	 * @var string
	 */
	public static string $hook_suffix = 'tickets_page_tec-tickets-admin-tickets';

	/**
	 * Whether the page has a header.
	 *
	 * @since 5.24.1
	 *
	 * @var bool
	 */
	public static bool $has_header = false;

	/**
	 * Whether the page has a footer.
	 *
	 * @since 5.24.1
	 *
	 * @var bool
	 */
	public static bool $has_footer = false;

	/**
	 * Whether the page has a sidebar.
	 *
	 * @since 5.24.1
	 *
	 * @var bool
	 */
	public static bool $has_sidebar = false;

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
	 * Defines whether the current page is the Event Tickets "All Tickets page.
	 *
	 * @since 5.14.0
	 *
	 * @return boolean
	 */
	public static function is_on_page(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$page_slug === $admin_page;
	}

	/**
	 * Add the admin page wrapper classes.
	 *
	 * @since 5.24.1
	 *
	 * @param array $classes The classes to add to the admin page wrapper.
	 *
	 * @return string[]
	 */
	public static function add_admin_page_wrapper_classes( array $classes ): array {
		if ( ! static::is_on_page() ) {
			return $classes;
		}

		$classes[] = 'tec-admin-page--header';
		$classes[] = 'tec-admin-page--simple';

		// remove 'tec-admin-page' from the classes array.
		$flipped_classes = array_flip( $classes );

		if ( isset( $flipped_classes['tec-admin-page'] ) ) {
			unset( $classes[ $flipped_classes['tec-admin-page'] ] );
		}

		return $classes;
	}

	/**
	 * Add the admin page header classes.
	 *
	 * @since 5.24.1
	 *
	 * @param array $classes The classes to add to the admin page wrapper.
	 *
	 * @return string[]
	 */
	public static function add_admin_page_header_classes( array $classes ): array {
		if ( ! static::is_on_page() ) {
			return $classes;
		}

		$classes[] = 'tec-admin-page__header--simple';

		return $classes;
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
			'page' => static::$page_slug,
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
	 * Get the page title.
	 *
	 * @since 5.24.1
	 */
	public function get_the_page_title(): string {
		return __( 'All Tickets', 'event-tickets' );
	}

	/**
	 * Get the menu title.
	 *
	 * @since 5.24.1
	 */
	public function get_the_menu_title(): string {
		return __( 'All Tickets', 'event-tickets' );
	}

	/**
	 * Get the parent page slug.
	 *
	 * @since 5.24.1
	 */
	public function get_parent_page_slug(): string {
		return static::$parent_slug;
	}

	/**
	 * Get the menu position.
	 *
	 * @since 5.24.1
	 *
	 * @return float The menu position.
	 */
	public function get_position(): float {
		return 1.2;
	}

	/**
	 * Maybe register the tabs if we're on our page.
	 *
	 * @since 5.24.1
	 *
	 * @return void
	 */
	public function maybe_register_tabs(): void {
		if ( ! static::is_on_page() ) {
			return;
		}

		$this->register_tabs();
	}

	/**
	 * Register a tab for the page.
	 * This method is public to allow external plugins to register their own tabs.
	 *
	 * @since 5.24.1
	 *
	 * @param string $slug  The tab's slug (used in URL and as key).
	 * @param string $label The tab's label.
	 * @param array  $args  {
	 *     Optional. Array of tab arguments.
	 *
	 *     @type bool   $visible    Whether the tab should be visible. Default true.
	 *     @type string $capability The capability required to see this tab. Default 'manage_options'.
	 *     @type bool   $active     Whether this is the active tab. Default false.
	 * }
	 */
	public function add_tab( string $slug, string $label, array $args = [] ): void {
		$this->register_tab( $slug, $label, $args );
	}

	/**
	 * Register the tabs for the page.
	 *
	 * @since 5.24.1
	 *
	 * @return void
	 */
	protected function register_tabs(): void {
		/**
		 * Action that fires before registering the admin tabs.
		 *
		 * @since 5.24.1
		 *
		 * @param Page $page The current page instance.
		 */
		do_action( 'tec_tickets_admin_tickets_page_before_register_tabs', $this );

		$this->register_tab(
			'ticket-table',
			__( 'Event Tickets', 'event-tickets' ),
			[
				'visible'    => true,
				'capability' => $this->required_capability(),
				'active'     => true,
			]
		);

		/**
		 * Action that fires after registering the admin tabs.
		 * Use this hook to register additional tabs.
		 *
		 * @since 5.24.1
		 *
		 * @param Page $page The current page instance.
		 */
		do_action( 'tec_tickets_admin_tickets_page_after_register_tabs', $this );
	}

	/**
	 * Render the "Event Tickets" tab content.
	 *
	 * @since 5.24.1
	 *
	 * @return void
	 */
	protected function render_ticket_table_tab_content(): void {
		tribe_asset_enqueue_group( 'event-tickets-admin-tickets' );

		$context = [
			'tickets_table'  => tribe( List_Table::class ),
			'page_slug'      => self::$page_slug,
			'tickets_exist'  => self::tickets_exist(),
			'edit_posts_url' => $this->get_link_to_edit_posts(),
		];

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		$admin_views->template( 'admin-tickets', $context );
	}

	/**
	 * Render the main content of the page.
	 *
	 * @since 5.24.1
	 */
	public function admin_page_main_content(): void {
		if ( empty( $this->tabs ) ) {
			$this->register_tabs();
		}

		$this->render_tabs();

		$this->render_tab_content();
	}

	/**
	 * Render the sidebar content.
	 *
	 * @since 5.24.1
	 */
	public function admin_page_sidebar_content(): void {
		// No sidebar content for now.
	}

	/**
	 * Render the footer content.
	 *
	 * @since 5.24.1
	 */
	public function admin_page_footer_content(): void {
		// No footer content for now.
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
}
