<?php

namespace Tribe\Tickets\Admin\Home;

use TEC\Common\Contracts\Service_Provider as Service_Provider_Contract;
use Tribe\Tickets\Admin\Settings;

/**
 * Class Manager
 *
 * @package Tribe\Tickets\Admin\Home
 *
 * @since 5.4.0
 */
class Service_Provider extends Service_Provider_Contract {
	/**
	 * Register the provider singletons.
	 *
	 * @since 5.4.0
	 */
	public function register() {

		$this->container->singleton( 'tickets.admin.home', self::class );
		$this->hooks();
	}

	/**
	 * Add actions and filters.
	 *
	 * @since 5.4.0
	 */
	protected function hooks() {
		if ( ! $this->is_home_page() ) {
			return;
		}

		add_filter( 'admin_body_class', [ $this, 'admin_body_class' ] );
	}

	/**
	 * Check if it's the home page.
	 *
	 * @since 5.4.0
	 *
	 * @return bool
	 */
	public function is_home_page() {
		$admin_page  = isset( $_GET['page'] ) ? $_GET['page'] : null;
		$parent_slug = tribe( Settings::class )::$parent_slug;

		return ! empty( $admin_page ) && $parent_slug === $admin_page;
	}

	/**
	 * Hooked to admin_body_class to add the class to the body tag.
	 *
	 * @since 5.4.0
	 *
	 * @param string $classes A space separated string of classes to be added to body.
	 *
	 * @return string $classes A set of classes to be added to the body tag.
	 */
	public function admin_body_class( $classes ) {
		$classes .= ' tribe-welcome';

		return $classes;
	}

	/**
	 * Display the home page for Event Tickets.
	 *
	 * @since 5.4.0
	 */
	public function display_home_page() {
		// @todo Move to a relevant class so the Service Provider avoids business logic.
		// We're temporary relying on the activation page, on the "welcome" context.
		$activation_page = tribe( 'admin.activation.page' );
		$plugin          = \Tribe__Tickets__Main::instance();
		$context         = 'welcome';
		$title           = esc_html( esc_html__( 'Welcome to Event Tickets!', 'event-tickets' ) );
		$template        = $plugin->plugin_path . 'src/admin-views/admin-welcome-message.php';

		if ( ! file_exists( $template ) || ! $this->is_home_page() ) {
			return '';
		}

		ob_start();
		include $template;
		$html = ob_get_clean();

		do_action( 'tribe_settings_top' );

		echo "
			<div class='tribe_settings tribe_{$context}_page wrap'>
				<h1> {$title} </h1>
				{$html}
			</div>
		";

		do_action( 'tribe_settings_bottom' );
	}

}
