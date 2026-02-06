<?php
namespace Tribe\Tickets\Admin;

use Tribe\Admin\Troubleshooting as Troubleshooting;
use Tribe__Settings_Tab;
use Tribe__Template;
use TEC\Common\Configuration\Configuration;
use TEC\Tickets\Admin\Help_Hub\ET_Hub_Resource_Data;
use TEC\Common\Admin\Help_Hub\Hub;

/**
 * Manages the admin settings UI in relation to ticket configuration.
 */
class Settings {

	/**
	 * Event Tickets menu page slug.
	 *
	 * @var string
	 */
	public static $parent_slug = 'tec-tickets';

	/**
	 * Event Tickets settings page slug.
	 *
	 * @var string
	 */
	public static $settings_page_id = 'tec-tickets-settings';

	/**
	 * Event Tickets Help page slug.
	 *
	 * @since 5.6.3
	 *
	 * @var string
	 */
	public static $help_page_id = 'tec-tickets-help';

	/**
	 * The Help Hub page slug.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $help_hub_slug = 'tec-tickets-help-hub';

	/**
	 * The Original Help page slug.
	 *
	 * @since 5.24.0
	 *
	 * @var string
	 */
	public static string $old_help_slug = 'tec-tickets-help';

	/**
	 * Event Tickets Help page slug.
	 *
	 * @since 5.6.3
	 *
	 * @var string
	 */
	public static $troubleshooting_page_id = 'tec-tickets-troubleshooting';

	/**
	 * Stores the instance of the settings tab.
	 *
	 * @since 5.23.0
	 *
	 * @var Tribe__Settings_Tab
	 */
	protected $settings_tab;

	/**
	 * Settings tabs.
	 */
	public $tabs = [];

	/**
	 * Returns the main admin tickets settings URL.
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ) {
		$defaults = [
			'page' => static::$settings_page_id,
		];

		// Allow the link to be "changed" on the fly.
		$args = wp_parse_args( $args, $defaults );

		$wp_url = is_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'admin.php' );

		// Keep the resulting URL args clean.
		$url = add_query_arg( $args, $wp_url );

		/**
		 * Filters the URL to the Event Tickets settings page.
		 *
		 * @since 5.4.0
		 *
		 * @param string $url The URL to the Event Tickets settings page.
		 */
		return apply_filters( 'tec_tickets_settings_url', $url );
	}

	/**
	 * Adds the Event Tickets settings page to the pages configuration.
	 *
	 * @since 5.4.0
	 *
	 * @param array $pages An array containing the slug of the pages with tabs.
	 *
	 * @return array $pages The modified array containing the pages with tabs.
	 */
	public function add_to_pages_with_tabs( $pages ) {
		$pages[] = static::$settings_page_id;

		return $pages;
	}

	/**
	 * Filter the Event Tickets Settings page title.
	 *
	 * @since 5.4.0
	 *
	 * @param string $title The title of the settings page.
	 *
	 * @return string The modified title of the settings page..
	 */
	public function settings_page_title( $title ) {
		if ( ! $this->is_tec_tickets_settings() ) {
			return $title;
		}

		return sprintf(
			// Translators: %1$s is the `Tickets` in plural.
			__( '%1$s Settings', 'event-tickets' ),
			tribe_get_ticket_label_plural( 'tec_tickets_settings_title' )
		);
	}

	/**
	 * Defines wether the current page is the Event Tickets Settings page.
	 *
	 * @since 5.4.0
	 *
	 * @return boolean
	 */
	public function is_tec_tickets_settings(): bool {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::$settings_page_id === $admin_page;
	}

	/**
	 * Check if the current page is on a specific tab for the Tickets settings.
	 *
	 * @since 5.5.9
	 *
	 * @param string $tab The tab name.
	 *
	 * @return boolean
	 */
	public function is_on_tab( $tab = '' ): bool {
		if ( ! $this->is_tec_tickets_settings() || empty( $tab ) ) {
			return false;
		}

		return tribe_get_request_var( 'tab' ) === $tab;
	}

	/**
	 * Check if the current page is on a specific tab for the Tickets settings.
	 *
	 * @since 5.6.3 Added the ability to also check `tc-section` request var.
	 * @since 5.5.9
	 *
	 * @param string $tab The tab name.
	 * @param string $section The section name.
	 *
	 * @return boolean
	 */
	public function is_on_tab_section( $tab = '', $section = '' ): bool {
		if ( ! $this->is_on_tab( $tab ) || empty( $section ) ) {
			return false;
		}

		return tribe_get_request_var( 'section' ) === $section || tribe_get_request_var( 'tc-section' ) === $section;
	}

	/**
	 * Get the icon for the Event Tickets settings page.
	 *
	 * @since 5.4.0
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" fill="#9ba2a6" viewBox="0 0 15.46 11.9"><g id="Layer_2" data-name="Layer 2"><g id="Layer_1-2" data-name="Layer 1"><path d="M15.33,7.47,13.84,6.09a.4.4,0,0,0-.55.08,1.17,1.17,0,0,1-.94.47,1.22,1.22,0,0,1-.84-2.07l0,0a.38.38,0,0,0,.12-.29A.4.4,0,0,0,11.55,4l-1.5-1.38a.37.37,0,0,0-.3-.07.36.36,0,0,0-.26.17A9.27,9.27,0,0,1,7.37,4.76l-.09.07c-2,1.52-3.76,2.11-5.39,1.87C1.4,6.63.75,6.38.79,5.09V5A7.19,7.19,0,0,1,2.67,1l1,1A1.72,1.72,0,0,0,5.19,4.45h.06a1.65,1.65,0,0,0,.88-.29l.16.16a.38.38,0,0,0,.55,0,.4.4,0,0,0,0-.56l-.41-.4a.43.43,0,0,0-.29-.1.4.4,0,0,0-.27.12.88.88,0,0,1-.68.3.92.92,0,0,1-.91-.92.9.9,0,0,1,.25-.62.41.41,0,0,0,0-.56L2.91.1l0,0a.39.39,0,0,0-.52.05A8.08,8.08,0,0,0,0,5.07,2.12,2.12,0,0,0,1,7.23l.26.23A52.11,52.11,0,0,0,5.54,11l.07,0a4.94,4.94,0,0,0,2.89.87c.27,0,.55,0,.84,0A10.43,10.43,0,0,0,15.39,8,.39.39,0,0,0,15.33,7.47Zm-6.06,3.6A4.39,4.39,0,0,1,6,10.33l-.11-.08C5.21,9.79,4.1,8.89,2.58,7.56l0,0h0A7.54,7.54,0,0,0,6.53,6.28l4.31,4.3A5.31,5.31,0,0,1,9.27,11.07Z"/></g></g></svg>' );

		/**
		 * Filter the menu icon for Events Tickets in the WordPress admin.
		 *
		 * @since TDB
		 *
		 * @param string $icon The menu icon for Event Tickets in the WordPress admin.
		 */
		return apply_filters( 'tec_tickets_menu_icon', $icon );
	}

	/**
	 * Adds the Event Tickets menu and pages.
	 *
	 * @since 5.4.0
	 * @since 5.9.1.1 Removed translation from the Title.
	 */
	public function add_admin_pages() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::$parent_slug,
				'path'     => static::$parent_slug,
				'title'    => 'Tickets',
				'icon'     => $this->get_menu_icon(),
				'position' => 7,
				'callback' => [
					tribe( 'tickets.admin.home' ),
					'display_home_page',
				],
			]
		);

		$admin_pages->register_page(
			[
				'id'       => static::$parent_slug,
				'path'     => static::$parent_slug,
				'parent'   => static::$parent_slug,
				'position' => 1,
				'title'    => esc_html__( 'Home', 'event-tickets' ),
			]
		);

		$admin_pages->register_page(
			[
				'id'       => static::$settings_page_id,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Settings', 'event-tickets' ),
				'path'     => static::$settings_page_id,
				'position' => 2,
				'callback' => [
					tribe( 'settings' ),
					'generate_page',
				],
			]
		);

		// Redirects users from the outdated Help page to the new Help Hub page if accessed.
		$this->redirect_to_help_hub();

		// Instantiate necessary dependencies for the Help Hub.
		$template      = tribe( Tribe__Template::class );
		$config        = tribe( Configuration::class );
		$resource_data = tribe( ET_Hub_Resource_Data::class );

		// Instantiate the Hub instance with all dependencies.
		$hub_instance = new Hub( $resource_data, $config, $template );

		$admin_pages->register_page(
			[
				'id'       => static::$help_page_id,
				'parent'   => static::$parent_slug,
				'title'    => esc_html__( 'Help', 'event-tickets' ),
				'path'     => self::$help_hub_slug,
				'position' => 3,
				'callback' => [ $hub_instance, 'render' ],
			]
		);

		$this->maybe_add_troubleshooting();
	}

	/**
	 * Redirects users from an outdated help page to the updated Help Hub page in the WordPress admin.
	 *
	 * Checks the `page` query parameters, and if they match the old help page slug.
	 *
	 * @since 5.24.0
	 *
	 * @return void
	 */
	public function redirect_to_help_hub(): void {
		$page = tribe_get_request_var( 'page' );

		// Exit if the request is not for the old help page.
		if ( self::$old_help_slug !== $page ) {
			return;
		}

		// Build the new URL for redirection.
		$new_url = add_query_arg(
			[
				'page' => self::$help_hub_slug,
			],
			admin_url( 'admin.php' )
		);

		//phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
		wp_safe_redirect( $new_url );
		tribe_exit();
	}

	/**
	 * Maybe add troubleshooting page for Event Tickets.
	 *
	 * @since 5.4.0
	 */
	public function maybe_add_troubleshooting() {
		$admin_pages = tribe( 'admin.pages' );

		if ( ! tribe( 'settings' )->should_setup_pages() ) {
			return;
		}

		$troubleshooting = tribe( Troubleshooting::class );

		$admin_pages->register_page(
			[
				'id'         => static::$troubleshooting_page_id,
				'parent'     => 'tec-tickets',
				'title'      => esc_html__( 'Troubleshooting', 'event-tickets' ),
				'path'       => static::$troubleshooting_page_id,
				'capability' => $troubleshooting->get_required_capability(),
				'position'   => 4,
				'callback'   => [
					$troubleshooting,
					'do_menu_page',
				],
			]
		);
	}

	/**
	 * Register the default settings tab sidebar.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function register_default_sidebar() {
		$sidebar = include_once tribe( 'tickets.main' )->plugin_path . 'src/admin-views/settings/sidebars/default-sidebar.php';
		Tribe__Settings_Tab::set_default_sidebar( $sidebar );
	}

	/**
	 * Loads the ticket settings from an admin-view file and returns them as an array.
	 *
	 * @since 4.10.9 Use customizable ticket name functions.
	 * @since 5.4.0 Use admin page and only show the General tab if we're in the Event Tickets menu.
	 *
	 * @param string $admin_page The admin page ID.
	 */
	public function settings_ui( $admin_page ) {
		if ( ! empty( $admin_page ) && static::$settings_page_id !== $admin_page ) {
			return;
		}

		$settings = $this->get_settings_array();

		$this->settings_tab          = new Tribe__Settings_Tab( 'event-tickets', esc_html__( 'General', 'event-tickets' ), $settings );
		$this->tabs['event-tickets'] = $this->settings_tab;
	}

	/**
	 * Gets the settings tab.
	 *
	 * @since 5.23.0
	 *
	 * @return Tribe__Settings_Tab
	 */
	public function get_settings_tab() {
		return $this->settings_tab;
	}

	/**
	 * Loads the timezone settings from an admin-view file and returns them as an array.
	 *
	 * @return array
	 */
	protected function get_settings_array() {
		include tribe( 'tickets.main' )->plugin_path . 'src/admin-views/tribe-options-tickets.php';

		/** @var array $tickets_tab Set in the file included above*/
		return $tickets_tab;
	}

	/**
	 * Maybe add network settings page for Event Tickets.
	 *
	 * @since 5.4.0
	 */
	public function maybe_add_network_settings_page() {
		$admin_pages = tribe( 'admin.pages' );
		$settings    = tribe( 'settings' );

		if ( ! is_plugin_active_for_network( 'event-tickets/event-tickets.php' ) ) {
			return;
		}

		$admin_pages->register_page(
			[
				'id'         => static::$settings_page_id,
				'parent'     => 'settings.php',
				'title'      => esc_html__( 'Tickets Settings', 'event-tickets' ),
				'path'       => static::$settings_page_id,
				'capability' => $admin_pages->get_capability( 'manage_network_options' ),
				'callback'   => [
					$settings,
					'generate_page',
				],
			]
		);
	}

	/**
	 * Generate network settings page for Event Tickets.
	 *
	 * @since 5.4.0
	 *
	 * @param string $admin_page The admin page ID.
	 */
	public function do_network_settings_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && static::$settings_page_id !== $admin_page ) {
			return;
		}

		include_once tribe( 'tickets.main' )->plugin_path . 'src/admin-views/tec-tickets-options-network.php';

		$this->tabs['network'] = new Tribe__Settings_Tab( 'network', esc_html__( 'Network', 'event-tickets' ), $networkTab );
	}

	/**
	 * Add the Event Tickets admin footer text.
	 *
	 * @since 5.4.0
	 *
	 * @param string $footer_text The admin footer text.
	 * @return string $footer_text The admin footer text, maybe modified.
	 */
	public function admin_footer_text_settings( $footer_text ) {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		if (
			! empty( $admin_page )
			&& self::$settings_page_id !== $admin_page
			&& self::$parent_slug !== $admin_page
		) {
			return $footer_text;
		}

		// Translators: %1$s: Opening `<a>` to Event Tickets rating page. %2$s: Closing `</a>` tag. %3$s: Five stars.
		$review_text = esc_html__( 'If you like %1$sEvent Tickets%2$s please leave us a %3$s. It takes a minute and it helps a lot.', 'event-tickets' );
		$review_url  = 'https://wordpress.org/support/plugin/event-tickets/reviews/?filter=5';

		$footer_text = sprintf(
			$review_text,
			'<strong>',
			'</strong>',
			'<a href="' . $review_url . '" target="_blank" rel="noopener noreferrer" class="tribe-rating">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
		);

		return $footer_text;
	}

	/**
	 * Get Tickets settings tab IDs.
	 *
	 * @since 5.4.0
	 *
	 * @return array $tabs Array of tabs IDs for the Tickets settings page.
	 */
	public function get_tickets_settings_tabs_ids() {
		$tabs = [
			'event-tickets',
		];

		/**
		 * Filters the tickets settings tab IDs.
		 *
		 * @since 5.4.0
		 *
		 * @param array $tabs Array of tabs IDs for the Tickets settings page.
		 */
		return apply_filters( 'tec_tickets_settings_tabs_ids', $tabs );
	}

	/**
	 * Maybe hijack the saving for the network settings page.
	 *
	 * @since 5.4.0
	 *
	 * @param array  $options Formatted the same as from get_options().
	 * @param string $admin_page The admin page being saved.
	 *
	 * @return array $options Formatted the same as from get_options(), maybe modified.
	 */
	public function maybe_hijack_save_network_settings( $options, $admin_page ) {
		// If we're saving the network settings page for tickets, bail.
		if ( ! empty( $admin_page ) && static::$settings_page_id === $admin_page ) {
			return $options;
		}

		if ( ! is_plugin_active_for_network( 'event-tickets/event-tickets.php' ) ) {
			return $options;
		}

		$tickets_tabs                     = $this->get_tickets_settings_tabs_ids();
		$form_options['hideSettingsTabs'] = $_POST['hideSettingsTabs'];

		// Iterate over the Tickets settings tab ids and merge the network settings.
		foreach ( $tickets_tabs as $tab => $key ) {
			if ( in_array( $key, $options['hideSettingsTabs'] ) ) {
				$_POST['hideSettingsTabs'][]        = $key;
				$form_options['hideSettingsTabs'][] = $key;
			}
		}

		return $form_options;
	}

	/**
	 * Get the settings page ID.
	 *
	 * @since 5.23.0
	 *
	 * @return string
	 */
	public function get_settings_page_id() {
		return self::$settings_page_id;
	}
}
