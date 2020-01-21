<?php

/**
 * Facilitates smoother integration with the Freemius.
 *
 * @since TBD
 */
class Tribe__Tickets__Integrations__Freemius {

	/**
	 * Stores the instance for the Freemius.
	 *
	 * @since  TBD
	 *
	 * @var Freemius
	 */
	private $instance;

	/**
	 * The object class used for assets.
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $object_class = 'Tribe__Tickets__Main';

	/**
	 * Stores the public key for Freemius.
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $public_key = 'pk_6dd9310b57c62871c59e58b8e739e';

	/**
	 * Stores the ID for the Freemius application.
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $freemius_id = '3841';

	/**
	 * Stores the slug for the Freemius application.
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $slug = 'event-tickets';

	/**
	 * Stores the name for the Freemius application.
	 *
	 * @since  TBD
	 *
	 * @var string
	 */
	private $name = 'Event Tickets';

	/**
	 * Store the value from the 'page' in the request.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $page;

	/**
	 * Store the value from the 'tab' in the request.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	private $tab;

	/**
	 * Tribe__Tickets__Integrations__Freemius constructor.
	 *
	 * @since TBD
	 */
	public function __construct() {
		$this->setup();
	}

	/**
	 * Performs setup for the Freemius integration singleton.
	 *
	 * @since  TBD
	 */
	public function setup() {
		if ( ! is_admin() ) {
			return;
		}

		global $pagenow;

		$this->page = tribe_get_request_var( 'page' );

		$valid_page = [
			Tribe__Settings::$parent_slug,
			Tribe__App_Shop::MENU_SLUG,
			'tribe-help',
		];

		if ( class_exists( 'Tribe__Events__Aggregator__Page' ) ) {
			$valid_page[] = Tribe__Events__Aggregator__Page::$slug;
		}

		if ( 'plugins.php' !== $pagenow && ! in_array( $this->page, $valid_page, true ) ) {
			return;
		}

		// If the common that loaded doesn't include Freemius, let's bail.
		if ( ! tribe()->offsetExists( 'freemius' ) ) {
			return;
		}

		/**
		 * Allows third-party disabling of the integration.
		 *
		 * @since  TBD
		 *
		 * @param bool $should_load Whether the Freemius integration should load.
		 */
		$should_load = apply_filters( 'tribe_tickets_integrations_should_load_freemius', true );

		if ( ! $should_load ) {
			return;
		}

		/** @var Tribe__Freemius $freemius */
		$freemius = tribe( 'freemius' );

		$this->instance = $freemius->initialize( $this->slug, $this->freemius_id, $this->public_key, [
			'menu'           => [
				'slug'    => $this->page,
				'account' => true,
				'support' => false,
			],
			'is_premium'     => false,
			'has_addons'     => false,
			'has_paid_plans' => false,
		] );

		if ( $this->instance->is_on() ) {
			return;
		}

		$this->instance->add_filter( 'connect_url', [ $this, 'redirect_settings_url' ] );
		$this->instance->add_filter( 'after_skip_url', [ $this, 'redirect_settings_url' ] );
		$this->instance->add_filter( 'after_connect_url', [ $this, 'redirect_settings_url' ] );
		$this->instance->add_filter( 'after_pending_connect_url', [ $this, 'redirect_settings_url' ] );

		tribe_asset( $this->object_class, 'tribe-tickets-freemius', 'freemius.css', [], 'admin_enqueue_scripts' );

		/*
		 * Freemius typically hooks this action–which bootstraps the deactivation dialog–during plugins_loaded, but we
		 * initialize our plugins AFTER plugins_loaded, so we'll register it on admin_init instead.
		 */
		add_action( 'admin_init', [ $this->instance, '_hook_action_links_and_register_account_hooks' ] );
		add_action( 'admin_init', [ $this, 'action_skip_activation' ] );

		$this->instance->add_filter( 'connect_message_on_update', [
			$this,
			'filter_connect_message_on_update',
		], 11, 6 );

		add_action( 'admin_init', [ $this, 'maybe_remove_activation_complete_notice' ] );
	}

	/**
	 * Redirect URL after the Freemius actions.
	 *
	 * @since TBD
	 *
	 * @return mixed
	 */
	public function redirect_settings_url() {
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			$url = sprintf( 'edit.php?post_type=%s&page=%s', Tribe__Events__Main::POSTTYPE, $this->page );
		} else {
			$url = sprintf( 'admin.php?page=%s', $this->page );
		}

		return admin_url( $url );
	}

	/**
	 * Action to skip activation since Freemius code does not skip correctly here.
	 *
	 * @since  TBD
	 *
	 * @return bool Whether activation was skipped.
	 */
	public function action_skip_activation() {
		$fs_action = tribe_get_request_var( 'fs_action' );

		// Prevent fatal errors.
		if ( ! function_exists( 'fs_redirect' ) || ! function_exists( 'fs_is_network_admin' ) ) {
			return false;
		}

		// Actually do the skipping of connection, since Freemius code does not do this.
		if ( $this->slug . '_skip_activation' !== $fs_action ) {
			return false;
		}

		check_admin_referer( $this->slug . '_skip_activation' );

		$this->instance->skip_connection( null, fs_is_network_admin() );

		fs_redirect( $this->instance->get_after_activation_url( 'after_skip_url' ) );

		return true;
	}

	/**
	 * Filter the content for the Freemius Popup.
	 *
	 * @since  TBD
	 *
	 * @param string $message         The message content.
	 * @param string $user_first_name The first name of user.
	 * @param string $product_title   The product title.
	 * @param string $user_login      The user_login of user.
	 * @param string $site_link       The site URL.
	 * @param string $freemius_link   The Freemius URL.
	 *
	 * @return string
	 */
	public function filter_connect_message_on_update(
		$message, $user_first_name, $product_title, $user_login, $site_link, $freemius_link
	) {
		// Add the heading HTML.
		$plugin_name = $this->name;
		$title       = '<h3>' . sprintf( esc_html__( 'We hope you love %1$s', 'event-tickets' ), $plugin_name ) . '</h3>';
		$html        = '';

		// Add the introduction HTML.
		$html .= '<p>';
		$html .= sprintf( esc_html__( 'Hi, %1$s! This is an invitation to help our %2$s community. If you opt-in, some data about your usage of %2$s will be shared with our teams (so they can work their butts off to improve). We will also share some helpful info on events management, WordPress, and our products from time to time.', 'event-tickets' ), $user_first_name, $plugin_name );
		$html .= '</p>';

		$html .= '<p>';
		$html .= sprintf( esc_html__( 'And if you skip this, that\'s okay! %1$s will still work just fine.', 'event-tickets' ), $plugin_name );
		$html .= '</p>';

		// Add the "Powered by" HTML.
		$html .= '<div class="tribe-powered-by-freemius">' . esc_html__( 'Powered by', 'event-tickets' ) . '</div>';

		return $title . $html;
	}

	/**
	 * Returns the instance of Freemius plugin.
	 *
	 * @since  TBD
	 *
	 * @return Freemius
	 */
	public function get() {
		return $this->instance;
	}

	/**
	 * Method to remove the sticky message when the plugin is active for Freemius.
	 *
	 * @since  TBD
	 */
	public function maybe_remove_activation_complete_notice() {
		// Bail if the is_pending_activation() method doesn't exist.
		if ( ! method_exists( $this->instance, 'is_pending_activation' ) ) {
			return;
		}

		// Bail if it's still pending activation.
		if ( $this->instance->is_pending_activation() ) {
			return;
		}

		$admin_notices = FS_Admin_Notices::instance( $this->slug, $this->name, $this->instance->get_unique_affix() );

		// Bail if it doesn't have the activation complete notice.
		if ( ! $admin_notices->has_sticky( 'activation_complete' ) ) {
			return;
		}

		// Remove the sticky notice for activation complete.
		$admin_notices->remove_sticky( 'activation_complete' );
	}
}
