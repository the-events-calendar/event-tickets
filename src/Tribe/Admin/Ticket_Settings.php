<?php

use Tribe\Admin\Troubleshooting as Troubleshooting;
/**
 * Manages the admin settings UI in relation to ticket configuration.
 */
class Tribe__Tickets__Admin__Ticket_Settings {

	/**
	 * Event Tickets settings page slug.
	 *
	 * @var string
	 */
	public static $settings_page_id = 'tec-tickets-settings';

	/**
	 * Sets up the display of timezone-related settings and listeners to deal with timezone-update
	 * requests (which are initiated from within the settings screen).
	 */
	public function __construct() {
		add_action( 'tribe_settings_do_tabs', [ $this, 'settings_ui' ] );
		add_action( 'admin_menu', [ $this, 'add_admin_pages' ] );
		add_action( 'network_admin_menu', [ $this, 'maybe_add_network_settings_page' ] );
		add_action( 'tribe_settings_do_tabs', [ $this, 'do_network_settings_tab' ], 400 );

		// @todo @juanfra: We'll need to add a method to sync the network settings.

		add_filter( 'tribe_settings_page_title', [ $this, 'settings_page_title' ] );
		add_filter( 'tec_admin_pages_with_tabs', [ $this, 'add_to_pages_with_tabs' ], 20, 1 );
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
	public function get_icon() {
		// @todo @juanfra: Define the icon for the menu.
		$icon_base64   = 'PHN2ZyB3aWR0aD0iNzYiIGhlaWdodD0iNTgiIHZpZXdCb3g9IjAgMCA3NiA1OCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTMwLjUgMjkuNUw1NS41IDU0IiBzdHJva2U9ImJsYWNrIiBzdHJva2Utd2lkdGg9IjIiLz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMi45ODI5IDAuMjU4Njk0TDEyLjg5NDIgMC4xODU4OTFDMTIuNDk2NCAtMC4xMDMzOTMgMTEuOTM1NiAtMC4wNTI1NTUyIDExLjU5NzMgMC4zMjEwNDRDNC4zNDkwOSA4LjMyNzcgMC4yMjA5MTggMTYuOTYxNCAwLjAwODQwODY0IDI0LjU2MTlDLTAuMTM3ODcgMjkuNzcyNyAxLjYzMTYgMzMuMDY3OSA0Ljc1MjUgMzQuNjg1Mkw2LjA3OTg5IDM1Ljg1ODhDMTYuNDY2OSA0NS4wMjU5IDIzLjU4NDUgNTAuOTAzMSAyNy40NjY0IDUzLjUxMzFMMjcuODMxMiA1My43NTUzQzMzLjUzMzEgNTcuNDkwOCAzOC42MTQxIDU4LjQ5MDYgNDYuMDAyIDU3Ljc2MjhDNTQuNDI1NyA1Ni45MzMgNjkuODQ2NCA0Ny43ODY3IDc1Ljc1NTMgMzguNjU5NEM3Ni4wMTI3IDM4LjI2MTggNzUuOTQ3NSAzNy43MzY4IDc1LjYwMDcgMzcuNDE0Mkw2OC4yNTIzIDMwLjU3ODdMNjguMTY0NyAzMC41MDYxQzY3Ljc0MjQgMzAuMTk1MyA2Ny4xMzgxIDMwLjI3MzMgNjYuODExNSAzMC43MDFDNjUuNDkwNyAzMi40MzEgNjMuNDY5MyAzMy40NjA0IDYxLjI3ODkgMzMuNDYwNEM1Ny40MTMzIDMzLjQ2MDQgNTQuMjczMiAzMC4yNzIxIDU0LjI3MzIgMjYuMzMyNEM1NC4yNzMyIDI0LjQxMjQgNTUuMDIxNyAyMi42MTU1IDU2LjMzMDQgMjEuMjg1OEw1Ni41Mjc2IDIxLjA5NTVDNTYuOTQwMSAyMC43MDg4IDU2LjkzODkgMjAuMDUzNiA1Ni41MjQ5IDE5LjY2ODVMNDkuMTYwNiAxMi44MTg2TDQ5LjA3NSAxMi43NDc1QzQ4LjYzMzMgMTIuNDIwNSA0Ny45OTU2IDEyLjUyMjcgNDcuNjgzIDEyLjk5MjdDNDUuMzQ4OSAxNi41MDExIDQxLjg4NiAxOS44MTEyIDM2Ljc3MzEgMjMuODIxMUwzNi4zMjI1IDI0LjE1NzNDMjYuMzgzOSAzMS41MzAyIDE3LjI5NTEgMzUuMTE5MSA4LjM3Mzc5IDMzLjc5MDVDNC4xODExMyAzMy4xNjUzIDEuNzk1OTYgMzAuNDU5NSAxLjk1OTk4IDI0LjYxNjZMMS45NzQ0OCAyNC4yMjkxQzIuMjg5NzQgMTcuNDkyOSA1LjkyNzEgOS43NzQzMyAxMi4yOTc2IDIuNDcxOTZMMTIuMzg5IDIuMzY3MTFMMTguOTY2MiA4LjQzNDk3TDE4Ljg3ODggOC41NTk3N0MxOC4wMzgxIDkuODAyMjQgMTcuNTc0NyAxMS4yNzk3IDE3LjU3NDcgMTIuODI3OEMxNy41NzQ3IDE3LjAyNDggMjAuOTU4MSAyMC40Mjk5IDI1LjEzNSAyMC40Mjk5TDI1LjQwMzggMjAuNDI1MUMyNy4xMDEzIDIwLjM2NTIgMjguNjk2NiAxOS43MzkxIDI5Ljk2ODQgMTguNjc0TDMwLjAxNzMgMTguNjMxNEwzMy40MzMgMjEuNzgxOEMzMy44MjkzIDIyLjE0NzMgMzQuNDQ2OCAyMi4xMjI0IDM0LjgxMjQgMjEuNzI2MkMzNS4xNzggMjEuMzI5OSAzNS4xNTMxIDIwLjcxMjMgMzQuNzU2OCAyMC4zNDY4TDMwLjY2NDcgMTYuNTcxN0MzMC4yNjU3IDE2LjIwMzYgMjkuNjQzMSAxNi4yMzE3IDI5LjI3ODkgMTYuNjM0MkMyOC4yMjI5IDE3LjgwMTMgMjYuNzM0MSAxOC40Nzc1IDI1LjEzNSAxOC40Nzc1QzIyLjAzOTIgMTguNDc3NSAxOS41MjcgMTUuOTQ5MyAxOS41MjcgMTIuODI3OEMxOS41MjcgMTEuMzg2MyAyMC4wNjQ2IDEwLjAzMTUgMjEuMDE2NSA4Ljk5NTMxQzIxLjM4MDYgOC41OTg4NyAyMS4zNTUxIDcuOTgyNDYgMjAuOTU5NSA3LjYxNzQ1TDEyLjk4MjkgMC4yNTg2OTRaTTQ4LjU1NzQgMTUuMTQ4TDQ4LjY1MzggMTUuMDEzTDU0LjQ3NDQgMjAuNDI3NUw1NC4zNDg1IDIwLjU3NzdDNTMuMDUwNCAyMi4xODI0IDUyLjMyMDkgMjQuMjAwMyA1Mi4zMjA5IDI2LjMzMjRDNTIuMzIwOSAzMS4zNDM3IDU2LjMyODMgMzUuNDEyNyA2MS4yNzg5IDM1LjQxMjdMNjEuNTY4NSAzNS40MDgxQzYzLjg3OTEgMzUuMzMzMiA2Ni4wMjU0IDM0LjM2NDYgNjcuNjE2MSAzMi43NDczTDY3LjY2MzUgMzIuNjk2OUw3My42NTk0IDM4LjI3NDVMNzMuNDgzNiAzOC41MjI1QzY3LjQ4MTEgNDYuODM5NiA1My4zNzEgNTUuMDc1MSA0NS44MTA2IDU1LjgxOTlDMzguNjkxMSA1Ni41MjEyIDM0LjAwMyA1NS41NTU1IDI4LjU1NTcgNTEuODkyOUwyNy45OTc4IDUxLjUxMDZDMjQuNjM3NyA0OS4xNjc1IDE5LjA3MSA0NC42MTE0IDExLjMxNTUgMzcuODU0MUw5LjAxNzcyIDM1Ljg0MzhDMTguMTA5IDM2Ljg3NzggMjcuMjEyMSAzMy4yNDc3IDM3LjAxMTYgMjYuMDc0N0wzNy45NjIxIDI1LjM2OTVMMzguNDgyMiAyNC45NTk5QzQyLjk3MzUgMjEuNDAyMyA0Ni4yMDc0IDE4LjM1MDUgNDguNTU3NCAxNS4xNDhaIiBmaWxsPSIjMEYxMDMxIi8+CjxwYXRoIGZpbGwtcnVsZT0iZXZlbm9kZCIgY2xpcC1ydWxlPSJldmVub2RkIiBkPSJNNTYuODI5MSA0My4zMDIzQzU1Ljg3MTIgNDQuMzQgNTUuOTM4IDQ1Ljk1NTUgNTYuOTc3NyA0Ni45MTE1QzU4LjAxODQgNDcuODY3NSA1OS42Mzk1IDQ3LjggNjAuNTk3MyA0Ni43NjI0QzYxLjU1NjEgNDUuNzI0NyA2MS40ODkzIDQ0LjEwOTIgNjAuNDQ4NyA0My4xNTQxQzU5LjQwOCA0Mi4xOTkgNTcuNzg3OCA0Mi4yNjQ3IDU2LjgyOTEgNDMuMzAyM1oiIGZpbGw9IiMwRjEwMzEiLz4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik00OS4xNDUxIDM2LjQ5MkM0OC4xODYzIDM3LjUyOTUgNDguMjU0IDM5LjE0NSA0OS4yOTM4IDQwLjFDNTAuMzM0NCA0MS4wNTY4IDUxLjk1NTUgNDAuOTg5NCA1Mi45MTM0IDM5Ljk1MThDNTMuODcyMiAzOC45MTQyIDUzLjgwNDUgMzcuMjk4OCA1Mi43NjM4IDM2LjM0MjhDNTEuNzIzMiAzNS4zODc4IDUwLjEwMyAzNS40NTQ0IDQ5LjE0NTEgMzYuNDkyWiIgZmlsbD0iIzBGMTAzMSIvPgo8cGF0aCBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGNsaXAtcnVsZT0iZXZlbm9kZCIgZD0iTTQxLjQ2MDQgMjkuNjgxNUM0MC41MDI1IDMwLjcxOTMgNDAuNTY5MyAzMi4zMzQ5IDQxLjYwOTEgMzMuMjkwMUM0Mi42NDk3IDM0LjI0NjIgNDQuMjcwOCAzNC4xNzg3IDQ1LjIyODcgMzMuMTQxQzQ2LjE4NzQgMzIuMTA0MSA0Ni4xMjA3IDMwLjQ4NzYgNDUuMDggMjkuNTMyNEM0NC4wMzk0IDI4LjU3NzIgNDIuNDE5MiAyOC42NDM4IDQxLjQ2MDQgMjkuNjgxNVoiIGZpbGw9IiMwRjEwMzEiLz4KPC9zdmc+Cg==';
		$icon_data_uri = 'data:image/svg+xml;base64,' . $icon_base64;

		return 'dashicons-tickets-alt'; //$icon_data_uri;
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
				'id'       => 'tec-tickets', // @todo @juanfra: this to constant (?)
				'path'     => 'tec-tickets',
				'title'    => esc_html__( 'Tickets', 'event-tickets' ),
				'icon'     => $this->get_icon(),
				'position' => 7,
			]
		);

		$admin_pages->register_page(
			[
				'id'     => 'tec-tickets', // @todo @juanfra: this to constant (?)
				'path'   => 'tec-tickets',
				'parent' => 'tec-tickets',
				'title'  => esc_html__( 'Home', 'event-tickets' ),
			]
		);

		$admin_pages->register_page(
			[
				'id'       => self::$settings_page_id,
				'parent'   => 'tec-tickets',
				'title'    => esc_html__( 'Settings', 'event-tickets' ),
				'path'     => self::$settings_page_id,
				'callback' => [
					tribe( 'settings' ),
					'generatePage'
				],
			]
		);

		$admin_pages->register_page(
			[
				'id'       => 'tec-tickets-help',
				'parent'   => 'tec-tickets',
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

		if ( ! $settings->should_setup_network_pages() ) {
			return;
		}

		$admin_pages->register_page(
			[
				'id'         => self::$settings_page_id,
				'parent'     => 'settings.php',
				'title'      => esc_html__( 'Tickets Settings', 'event-tickets' ),
				'path'       => self::$settings_page_id,
				'capability' => $admin_pages->get_capability(),
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
	 */
	public function do_network_settings_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && self::$settings_page_id !== $admin_page ) {
			return;
		}

		include_once Tribe__Tickets__Main::instance()->plugin_path . 'src/admin-views/tec-tickets-options-network.php';

		new Tribe__Settings_Tab( 'network', esc_html__( 'Network', 'event-tickets' ), $networkTab );
	}
}
