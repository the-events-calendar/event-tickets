<?php
namespace Tribe\Tickets\Admin;

class Provider extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Register implementations.
	 *
	 * @since TDB
	 */
	public function register() {
		tribe_singleton( Settings::class, Settings::class );

		$this->add_hooks();
	}

	/**
	 * Add hooks.
	 *
	 * @since 5.4.0
	 */
	public function add_hooks() {
		add_action( 'tribe_settings_do_tabs', tribe_callback( Settings::class, 'settings_ui' ) );
		add_action( 'admin_menu', tribe_callback( Settings::class, 'add_admin_pages' ) );
		add_action( 'network_admin_menu', tribe_callback( Settings::class, 'maybe_add_network_settings_page' ) );
		add_action( 'tribe_settings_do_tabs', tribe_callback( Settings::class, 'do_network_settings_tab' ), 400 );
		// Set priority to 50 to overwrite any other sidebars that are currently registered.
		add_action( 'tribe_settings_do_tabs', $this->container->callback( Settings::class, 'register_default_sidebar' ), 50 );
		add_filter( 'tec_sidebar_allowed_admin_page', tribe_callback( Settings::class, 'get_settings_page_id' ) );


		add_filter( 'tribe_settings_page_title', tribe_callback( Settings::class, 'settings_page_title' ) );
		add_filter( 'tec_admin_pages_with_tabs', tribe_callback( Settings::class, 'add_to_pages_with_tabs' ), 20, 1 );
		add_filter( 'tec_admin_footer_text', tribe_callback( Settings::class, 'admin_footer_text_settings' ) );
		add_filter( 'tribe-events-save-network-options', tribe_callback( Settings::class, 'maybe_hijack_save_network_settings' ), 10, 2 );
	}
}
