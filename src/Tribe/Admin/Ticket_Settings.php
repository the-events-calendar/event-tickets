<?php

use Tribe\Admin\Troubleshooting as Troubleshooting;
/**
 * Manages the admin settings UI in relation to ticket configuration.
 */
class Tribe__Tickets__Admin__Ticket_Settings {

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
	 * Settings page hooks.
	 */
	public function __construct() {
		add_action( 'tribe_settings_do_tabs', [ $this, 'settings_ui' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_pages' ] );
		add_action( 'network_admin_menu', [ $this, 'maybe_add_network_settings_page' ] );
		add_action( 'tribe_settings_do_tabs', [ $this, 'do_network_settings_tab' ], 400 );

		add_filter( 'tribe_settings_page_title', [ $this, 'settings_page_title' ] );
		add_filter( 'tec_admin_pages_with_tabs', [ $this, 'add_to_pages_with_tabs' ], 20, 1 );
		add_filter( 'tec_admin_footer_text', [ $this, 'admin_footer_text_settings' ] );
		add_filter( 'tribe-events-save-network-options', [ $this, 'maybe_hijack_save_network_settings' ], 10, 2 );
	}

	/**
	 * Returns the main admin tickets settings URL.
	 *
	 * @param array $args Arguments to pass to the URL.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ) {
		$defaults = [
			'page' => self::$settings_page_id,
		];

		// Allow the link to be "changed" on the fly.
		$args = wp_parse_args( $args, $defaults );

		$wp_url = is_network_admin() ? network_admin_url( 'settings.php' ) : admin_url( 'admin.php' );

		// Keep the resulting URL args clean.
		$url = add_query_arg( $args, $wp_url );

		/**
		 * Filters the URL to the Event Tickets settings page.
		 *
		 * @since TBD
		 *
		 * @param string $url The URL to the Event Tickets settings page.
		 */
		return apply_filters( 'tec_tickets_settings_url', $url );
	}

	/**
	 * Adds the Event Tickets settings page to the pages configuration.
	 *
	 * @since TBD
	 *
	 * @param array $pages An array containing the slug of the pages with tabs.
	 *
	 * @return array $pages The modified array containing the pages with tabs.
	 */
	public function add_to_pages_with_tabs( $pages ) {
		$pages[] = self::$settings_page_id;

		return $pages;
	}

	/**
	 * Filter the Event Tickets Settings page title.
	 *
	 * @since TBD
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
			// Translators: %s is the `Tickets` in plural.
			__( '%s Settings', 'event-tickets' ),
			tribe_get_ticket_label_plural( 'tec_tickets_settings_title' )
		);
	}

	/**
	 * Defines wether the current page is the Event Tickets Settings page.
	 *
	 * @since TBD
	 *
	 * @return boolean
	 */
	public function is_tec_tickets_settings() {
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && self::$settings_page_id === $admin_page;
	}

	/**
	 * Get the icon for the Event Tickets settings page.
	 *
	 * @since TBD
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return 'dashicons-tickets-alt';
	}

	/**
	 * Adds the Event Tickets menu and pages.
	 *
	 * @since TBD
	 */
	public function add_admin_pages() {
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => self::$parent_slug,
				'path'     => self::$parent_slug,
				'title'    => esc_html__( 'Tickets', 'event-tickets' ),
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
				'id'     => self::$parent_slug,
				'path'   => self::$parent_slug,
				'parent' => self::$parent_slug,
				'title'  => esc_html__( 'Home', 'event-tickets' ),
			]
		);

		$admin_pages->register_page(
			[
				'id'       => self::$settings_page_id,
				'parent'   => self::$parent_slug,
				'title'    => esc_html__( 'Settings', 'event-tickets' ),
				'path'     => self::$settings_page_id,
				'callback' => [
					tribe( 'settings' ),
					'generatePage',
				],
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-help',
				'parent'   => self::$parent_slug,
				'title'    => esc_html__( 'Help', 'event-tickets' ),
				'path'     => 'tec-tickets-help',
				'callback' => [
					tribe( 'settings.manager' ),
					'do_help_tab',
				],
			]
		);

		$this->maybe_add_troubleshooting();
	}

	/**
	 * Maybe add troubleshooting page for Event Tickets.
	 *
	 * @since TBD
	 */
	public function maybe_add_troubleshooting() {
		$admin_pages = tribe( 'admin.pages' );

		if ( ! Tribe__Settings::instance()->should_setup_pages() ) {
			return;
		}

		$troubleshooting = tribe( Troubleshooting::class );

		$admin_pages->register_page(
			[
				'id'         => 'tec-tickets-troubleshooting',
				'parent'     => 'tec-tickets',
				'title'      => esc_html__( 'Troubleshooting', 'event-tickets' ),
				'path'       => 'tec-tickets-troubleshooting',
				'capability' => $troubleshooting->get_required_capability(),
				'callback'   => [
					$troubleshooting,
					'do_menu_page',
				],
			]
		);
	}

	/**
	 * Loads the ticket settings from an admin-view file and returns them as an array.
	 *
	 * @since 4.10.9 Use customizable ticket name functions.
	 * @since TBD Use admin page and only show the General tab if we're in the Event Tickets menu.
	 *
	 * @param string $admin_page The admin page ID.
	 */
	public function settings_ui( $admin_page ) {
		if ( ! empty( $admin_page ) && self::$settings_page_id !== $admin_page ) {
			return;
		}

		$settings = $this->get_settings_array();

		new Tribe__Settings_Tab( 'event-tickets', esc_html__( 'General', 'event-tickets' ), $settings );
	}

	/**
	 * Loads the timezone settings from an admin-view file and returns them as an array.
	 *
	 * @return array
	 */
	protected function get_settings_array() {
		$plugin_path = Tribe__Tickets__Main::instance()->plugin_path;
		include $plugin_path . 'src/admin-views/tribe-options-tickets.php';

		/** @var array $tickets_tab Set in the file included above*/
		return $tickets_tab;
	}

	/**
	 * Maybe add network settings page for Event Tickets.
	 *
	 * @since TBD
	 */
	public function maybe_add_network_settings_page() {
		$admin_pages = tribe( 'admin.pages' );
		$settings    = Tribe__Settings::instance();

		if ( ! is_plugin_active_for_network( 'event-tickets/event-tickets.php' ) ) {
			return;
		}

		$admin_pages->register_page(
			[
				'id'         => self::$settings_page_id,
				'parent'     => 'settings.php',
				'title'      => esc_html__( 'Tickets Settings', 'event-tickets' ),
				'path'       => self::$settings_page_id,
				'capability' => $admin_pages->get_capability( 'manage_network_options' ),
				'callback'   => [
					$settings,
					'generatePage',
				],
			]
		);
	}

	/**
	 * Generate network settings page for Event Tickets.
	 *
	 * @since TBD
	 *
	 * @param string $admin_page The admin page ID.
	 */
	public function do_network_settings_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && self::$settings_page_id !== $admin_page ) {
			return;
		}

		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/tec-tickets-options-network.php';

		new Tribe__Settings_Tab( 'network', esc_html__( 'Network', 'event-tickets' ), $networkTab );
	}

	/**
	 * Add the Event Tickets admin footer text.
	 *
	 * @since TBD
	 *
	 * @param string $footer_text The admin footer text.
	 * @param string $footer_text The admin footer text, maybe modified.
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
	 * @since TBD
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
		 * @since TBD
		 *
		 * @param array $tabs Array of tabs IDs for the Tickets settings page.
		 */
		return apply_filters( 'tec_tickets_settings_tabs_ids', $tabs );
	}

	/**
	 * Maybe hijack the saving for the network settings page.
	 *
	 * @since TBD
	 *
	 * @param array  $options Formatted the same as from get_options().
	 * @param string $admin_page The admin page being saved.
	 *
	 * @return array $options Formatted the same as from get_options(), maybe modified.
	 */
	public function maybe_hijack_save_network_settings( $options, $admin_page ) {
		// If we're saving the network settings page for tickets, bail.
		if ( ! empty( $admin_page ) && self::$settings_page_id === $admin_page ) {
			return $options;
		}

		$tickets_tabs = $this->get_tickets_settings_tabs_ids();

		// Iterate over the TEC settings tab ids and merge the network settings.
		foreach ( $tickets_tabs as $tab => $key ) {
			if ( in_array( $key, $options['hideSettingsTabs'] ) ) {
				$_POST['hideSettingsTabs'][] = $key;
				$options['hideSettingsTabs'] = $key;
			}
		}

		return $options;
	}
}
