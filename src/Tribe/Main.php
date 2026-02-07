<?php
/**
 * Main plugin class.
 */

use Tribe\Tickets\Admin\Provider as Admin_Provider;
use Tribe\Tickets\Events\Service_Provider as Events_Service_Provider;
use Tribe\Tickets\Promoter\Service_Provider as Promoter_Service_Provider;
use Tribe\Tickets\Admin\Settings;
use TEC\Common\StellarWP\Assets\Config as Assets_Config;
use TEC\Tickets\Admin\Onboarding\Tickets_Landing_Page;

/**
 * Class Tribe__Tickets__Main.
 */
class Tribe__Tickets__Main {

	/**
	 * Current version of this plugin.
	 */
	const VERSION = '5.27.4';

	/**
	 * Used to store the version history.
	 *
	 * @since 4.11.0
	 *
	 * @var string
	 */
	public $version_history_slug = 'previous_event_tickets_versions';

	/**
	 * Used to store the latest version.
	 *
	 * @since 4.11.0
	 *
	 * @var string
	 */
	public $latest_version_slug = 'latest_event_tickets_version';

	/**
	 * Min Version of WordPress.
	 *
	 * @since 4.10
	 *
	 * @var string
	 */
	protected $min_wordpress = '4.9';

	/**
	 * Min Version of PHP.
	 *
	 * @since 4.10
	 *
	 * @var string
	 */
	protected $min_php = '7.4.0';

	/**
	 * Min Version of The Events Calendar.
	 *
	 * @since 4.10
	 *
	 * @var string
	 */
	protected $min_tec_version = '6.15.12-dev';

	/**
	 * Name of the provider.
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Directory of the plugin.
	 *
	 * @var string
	 */
	public $plugin_dir;

	/**
	 * Path of the plugin.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * URL of the plugin.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * @var Tribe__Tickets__Legacy_Provider_Support
	 */
	public $legacy_provider_support;

	/**
	 * @var Tribe__Tickets__Shortcodes__User_Event_Confirmation_List
	 */
	private $user_event_confirmation_list_shortcode;

	/**
	 * @var Tribe__Tickets__Admin__Move_Tickets
	 */
	protected $move_tickets;

	/**
	 * @var Tribe__Tickets__Attendance_Totals
	 */
	protected $attendance_totals;

	/**
	 * @var Tribe__Tickets__Admin__Move_Ticket_Types
	 */
	protected $move_ticket_types;

	/**
	 * @deprecated 5.23.0
	 *
	 * @var Tribe__Admin__Activation_Page
	 */
	protected $activation_page;

	/**
	 * @var Tribe__Tickets__Plugin_Register
	 */
	protected $registered;

	/**
	 * @var bool Prevent autoload initialization
	 */
	private $should_prevent_autoload_init = false;

	/**
	 * @var string tribe-common VERSION regex
	 */
	private $common_version_regex = "/const\s+VERSION\s*=\s*'([^']+)'/m";

	/**
	 * Static Singleton Holder
	 * @var self
	 */
	protected static $instance;

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @return self
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Where in the themes we will look for templates
	 *
	 * @since 4.9
	 *
	 * @var string
	 */
	public $template_namespace = 'tickets';

	/**
	 * Class constructor
	 */
	protected function __construct() {
		// Set up some of the plugin's properties.
		$this->plugin_slug = 'tickets';
		$this->plugin_path = trailingslashit( EVENT_TICKETS_DIR );
		$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );

		$dir_prefix = str_contains( EVENT_TICKETS_DIR, '/vendor/' )
			? basename( dirname( EVENT_TICKETS_DIR, 2 ) ) . '/vendor/'
			: '';

		$this->plugin_url = trailingslashit( plugins_url( $dir_prefix . $this->plugin_dir ) );
	}

	/**
	 * Set the plugin name.
	 *
	 * @since 5.19.1
	 */
	public function set_plugin_name() {
		$this->plugin_name = esc_html_x( 'Tickets', 'provider_plugin_name', 'event-tickets' );
	}

	/**
	 * Attach our initial hooks and filters
	 *
	 * @since 5.18.0
	 * @since 5.19.1 Called `set_plugin_name` on `init` hook.
	 *
	 * @return void
	 */
	public function do_hooks() {
		add_action( 'init', [ $this, 'set_plugin_name' ] );
		add_filter( 'tribe_events_integrations_should_load_freemius', '__return_false' );

		$this->maybe_set_common_lib_info();

		add_action( 'plugins_loaded', [ $this, 'should_autoload' ], -3 );
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], -2 );

		register_activation_hook( EVENT_TICKETS_MAIN_PLUGIN_FILE, [ $this, 'on_activation' ] );
		register_deactivation_hook( EVENT_TICKETS_MAIN_PLUGIN_FILE, [ $this, 'on_deactivation' ] );
	}

	/**
	 * Fires when the plugin is activated.
	 */
	public function on_activation() {
		// Will be used to set up Stripe webhook on admin_init.
		set_transient( 'tec_tickets_commerce_setup_stripe_webhook', true );

		// Set plugin activation time for all installs.
		if ( ! is_admin() ) {
			return;
		}

		// Avoid a race condition and fatal by waiting until Common is loaded before we try to run this.
		add_action(
			'tribe_common_loaded',
			[ $this, 'set_activation_time' ]
		);

		$this->redirect_to_wizard_on_activation();
	}

	/**
	 * Redirect to the wizard on activation.
	 *
	 * @since 5.23.0
	 *
	 * @return void
	 */
	public function redirect_to_wizard_on_activation() {
		if ( is_network_admin() ) {
			// Never redirect on network admin.
			return;
		}

		// Get the checked plugins from the request. If there are more than one, we're doing a bulk activation.
		$checked = isset( $_POST['checked'] ) ? count( $_POST['checked'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! empty( $checked ) ) {
			// If multiple plugins are being activated, set the wizard redirect transient, this should only trigger redirection on a ET admin page visit.
			set_transient( '_tec_tickets_wizard_redirect', 1, 30 );
		} else {
			// If a single plugin is being activated, set the activation redirect transient for immediate redirection.
			set_transient( '_tec_tickets_activation_redirect', 1, 30 );
		}
	}
	/**
	 * Set the plugin activation time.
	 * Activated on plugin activation, runs on tribe_common_loaded.
	 *
	 * @since 5.5.9
	 *
	 * @return void
	 */
	public function set_activation_time() {
		tribe_update_option( 'tec_tickets_activation_time', time() );
	}

	/**
	 * Fires when the plugin is deactivated.
	 *
	 * @since 5.5.9
	 */
	public function on_deactivation() {
		// Remove plugin activation time on deactivation.
		if ( is_admin() ) {
			tribe_remove_option( 'tec_tickets_activation_time' );
		}

		tribe( TEC\Tickets\Commerce\Gateways\Stripe\Webhooks::class )->disable_webhook();
	}

	/**
	 * Setup of Common Library
	 */
	public function maybe_set_common_lib_info() {

		$common_version = file_get_contents( $this->plugin_path . 'common/src/Tribe/Main.php' );

		// if there isn't a tribe-common version, bail.
		if ( ! preg_match( $this->common_version_regex, $common_version, $matches ) ) {
			add_action( 'admin_head', [ $this, 'missing_common_libs' ] );

			return;
		}

		$common_version = $matches[1];

		/**
		 * If we don't have a version of Common or a Older version of the Lib
		 * overwrite what should be loaded by the auto-loader
		 */
		if (
			empty( $GLOBALS['tribe-common-info'] )
			|| version_compare( $GLOBALS['tribe-common-info']['version'], $common_version, '<' )
		) {
			$GLOBALS['tribe-common-info'] = [
				'dir'     => "{$this->plugin_path}common/src/Tribe",
				'version' => $common_version,
			];
		}
	}

	/**
	 * Resets the global common info back to TEC's common path
	 *
	 * @since 4.10.6.2
	 */
	private function reset_common_lib_info_back_to_tec() {
		if ( ! class_exists( 'Tribe__Events__Main', false ) ) {
			return;
		}

		// if we get in here, we need to reset the global common to TEC's version so that we don't cause a fatal
		$tec         = Tribe__Events__Main::instance();
		$main_source = file_get_contents( $tec->plugin_path . 'common/src/Tribe/Main.php' );

		// if there isn't a VERSION, don't override the common path
		if ( ! preg_match( $this->common_version_regex, $main_source, $matches ) ) {
			return;
		}

		$GLOBALS['tribe-common-info'] = [
			'dir'     => "{$tec->plugin_path}common/src/Tribe",
			'version' => $matches[1],
		];
	}

	/**
	 * Handles the soft-disabling of the plugin if requirements are not met.
	 *
	 * @since 5.9.3
	 */
	public function should_autoload(): void {
		$invalid = $this->maybe_bail_if_invalid_wp_or_php();
		$invalid = $this->maybe_bail_if_old_tec_is_present() || $invalid;

		if ( ! $invalid ) {
			return;
		}

		// Include dummy functions to prevent fatals with other plugins.
		require_once $this->plugin_path . 'src/functions/soft-disable.php';

		// If we get here, we need to reset the global common to TEC's version so that we don't cause a fatal.
		$this->reset_common_lib_info_back_to_tec();

		$this->should_prevent_autoload_init = true;
	}

	/**
	 * Prevents bootstrapping and autoloading if the version of TEC that is running is too old
	 *
	 * @since 4.10.6.2
	 * @since 5.9.3 added boolean "invalid" return value. True if the check fails, false if it passes.
	 */
	public function maybe_bail_if_old_tec_is_present(): bool {
		// early check for an older version of The Events Calendar to prevent fatal error
		if ( ! class_exists( 'Tribe__Events__Main', false ) ) {
			return false;
		}

		if ( version_compare( Tribe__Events__Main::VERSION, $this->min_tec_version, '>=' ) ) {
			return false;
		}

		add_action( 'admin_notices', [ $this, 'tec_compatibility_notice' ] );
		add_action( 'network_admin_notices', [ $this, 'tec_compatibility_notice' ] );
		add_action( 'tribe_plugins_loaded', [ $this, 'remove_exts' ], 0 );
		/*
		 * After common was loaded by another source (e.g. The Events Calendar) let's append this plugin source files
		 * to the ones the Autoloader will search. Since we're appending them, the ones registered by the plugin
		 * "owning" common will be searched first.
		 */
		add_action( 'tribe_common_loaded', [ $this, 'register_plugin_autoload_paths' ] );

		return true;
	}

	/**
	 * Prevents bootstrapping and autoloading if the version of WP or PHP are too old
	 *
	 * @since 4.10.6.2
	 * @since 5.9.3 added boolean "invalid" return value. True if the check fails, false if it passes.
	 */
	public function maybe_bail_if_invalid_wp_or_php(): bool {
		if ( self::supported_version( 'wordpress' ) && self::supported_version( 'php' ) ) {
			return false;
		}

		add_action( 'admin_notices', [ $this, 'not_supported_error' ] );
		add_action( 'network_admin_notices', [ $this, 'not_supported_error' ] );

		return true;
	}

	/**
	 * Finalize the initialization of this plugin
	 */
	public function plugins_loaded() {
		if ( $this->should_prevent_autoload_init ) {
			/**
			 * Fires if Event Tickets cannot load due to compatibility or other problems.
			 */
			do_action( 'tribe_tickets_plugin_failed_to_load' );
			return;
		}

		/**
		 * Before any methods from this plugin are called, we initialize our Autoloading
		 * After this method we can use any `Tribe__` classes
		 */
		$this->init_autoloading();

		add_filter( 'tec_common_parent_plugin_file', [ $this, 'include_parent_plugin_path_to_common' ] );

		// Start Up Common.
		Tribe__Main::instance();

		add_action( 'tribe_common_loaded', [ $this, 'bootstrap' ], 0 );
	}

	/**
	 * Adds our main plugin file to the list of paths.
	 *
	 * @since 6.1.0
	 *
	 *
	 * @param array<string> $paths The paths to TCMN parent plugins.
	 *
	 * @return array<string>
	 */
	public function include_parent_plugin_path_to_common( $paths ): array {
		$paths[] = EVENT_TICKETS_MAIN_PLUGIN_FILE;

		return $paths;
	}

	/**
	 * Load Text Domain on tribe_common_loaded as it requires common
	 *
	 * @since 4.10
	 * @since 5.23.0 Added Tyson group paths.
	 */
	public function bootstrap() {
		/*
		* Register the `/build` directory assets as a different group to ensure back-compatibility.
		* This needs to happen early in the plugin bootstrap routine.
		*/
		Assets_Config::add_group_path(
			self::class,
			$this->plugin_path,
			'build/',
			true
		);

		/*
		* Register the `/build` directory as root for packages.
		* The difference from the group registration above is that packages are not expected to use prefix directories
		* like `/js` or `/css`.
		*/
		Assets_Config::add_group_path(
			self::class . '-packages',
			$this->plugin_path,
			'build/',
			false
		);

		$this->hooks();

		$this->register_active_plugin();

		$this->bind_implementations();
		$this->user_event_confirmation_list_shortcode();
		$this->move_tickets();
		$this->move_ticket_types();

		Tribe__Tickets__JSON_LD__Order::hook();
		Tribe__Tickets__JSON_LD__Type::hook();

		/** @var Tribe__Tickets__Privacy */
		tribe( 'tickets.privacy' );

		/**
		 * Fires when Event Tickets is fully loaded.
		 *
		 * @since 5.22.0
		 */
		do_action( 'tec_tickets_fully_loaded' );

		/**
		 * Fires once Event Tickets has completed basic setup.
		 *
		 * @deprecated 5.22.0 Use `tec_tickets_fully_loaded` instead.
		 */
		do_action_deprecated( 'tribe_tickets_plugin_loaded', [], '5.22.0', 'Use `tec_tickets_fully_loaded` instead.' );
	}

	/**
	 * Registers the implementations in the container
	 *
	 * @since 4.7
	 */
	public function bind_implementations() {
		tribe_singleton( 'tickets.main', $this );

		// Initialize the Service Provider for Tickets.
		tribe_register_provider( Tribe__Tickets__Service_Provider::class );

		// Tickets Commerce providers.
		tribe_register_provider( TEC\Tickets\Provider::class );

		tribe_singleton( 'tickets.rsvp', new Tribe__Tickets__RSVP );
		tribe_singleton( 'tickets.commerce.cart', 'Tribe__Tickets__Commerce__Cart' );
		tribe_singleton( 'tickets.commerce.currency', 'Tribe__Tickets__Commerce__Currency', [ 'hook' ] );
		tribe_singleton( 'tickets.commerce.paypal', new Tribe__Tickets__Commerce__PayPal__Main );
		tribe_singleton( 'tickets.redirections', 'Tribe__Tickets__Redirections' );

		tribe_register_provider( TEC\Tickets\Configuration\Provider::class );

		tribe_singleton( Tribe__Tickets__Theme_Compatibility::class, Tribe__Tickets__Theme_Compatibility::class );

		// Event Tickets Provider to manage Events.
		tribe_register_provider( Events_Service_Provider::class );

		// ORM.
		tribe_register_provider( 'Tribe__Tickets__Service_Providers__ORM' );

		// REST API v1
		tribe_register_provider( 'Tribe__Tickets__REST__V1__Service_Provider' );
		// REST Editor APIs
		tribe_register_provider( 'Tribe__Tickets__Editor__REST__V1__Service_Provider' );

		// Blocks editor
		tribe_register_provider( TEC\Tickets\Blocks\Controller::class );

		// Privacy
		tribe_singleton( 'tickets.privacy', 'Tribe__Tickets__Privacy', [ 'hook' ] );

		// Views V2
		tribe_register_provider( Tribe\Tickets\Events\Views\V2\Service_Provider::class );

		// Admin home.
		tribe_register_provider( Tribe\Tickets\Admin\Home\Service_Provider::class );

		// Admin settings.
		tribe_register_provider( Tribe\Tickets\Admin\Settings\Service_Provider::class );

		// Admin manager.
		tribe_register_provider( Tribe\Tickets\Admin\Manager\Service_Provider::class );

		// Promoter.
		tribe_register_provider( Promoter_Service_Provider::class );

		// Admin provider.
		tribe_register_provider( Admin_Provider::class );

		// Set up IAN Client - In-App Notifications.
		tribe_register_provider( TEC\Tickets\Notifications\Provider::class );

		/**
		 * Allows other plugins and services to override/change the bound implementations.
		 *
		 * DO NOT put anything after this unless you _need to_ and know the implications!
		 *
		 * @since 5.23.0
		 */
		do_action( 'tec_tickets_bound_implementations' );
	}

	/**
	 * Registers this plugin as being active for other tribe plugins and extensions
	 */
	protected function register_active_plugin() {
		$this->registered = new Tribe__Tickets__Plugin_Register();
	}

	/**
	 * Hooked to admin_notices, this error is thrown when Event Tickets is run alongside a version of
	 * TEC that is too old
	 */
	public function tec_compatibility_notice() {
		$active_plugins = get_option( 'active_plugins' );

		$plugin_short_path = null;

		foreach ( $active_plugins as $plugin ) {
			if ( false !== strstr( $plugin, 'the-events-calendar.php' ) ) {
				$plugin_short_path = $plugin;
				break;
			}
		}

		$upgrade_path = wp_nonce_url(
			add_query_arg(
				[
					'action' => 'upgrade-plugin',
					'plugin' => $plugin_short_path,
				], get_admin_url() . 'update.php'
			), 'upgrade-plugin_' . $plugin_short_path
		);

		$min_version = str_replace( '-dev', '', $this->min_tec_version );

		$output = '<div class="error">';
		// Translators: %1$s is the min required version of The Events Calendar. %2$s Is the update link opening `<a>`. %3$s Is the update link closing `</a>`.
		$output .= '<p>' . sprintf( __( 'When The Events Calendar and Event Tickets are both activated, The Events Calendar must be running version %1$s or greater. Please %2$supdate now.%3$s', 'event-tickets' ), $min_version, '<a href="' . esc_url( $upgrade_path ) . '">', '</a>' ) . '</p>';
		$output .= '</div>';

		echo $output;
	}

	/**
	 * Prevents Extensions from running if TEC is on an Older Version
	 *
	 * @since 4.10.0.1
	 *
	 */
	public function remove_exts() {

		remove_all_actions( 'tribe_plugins_loaded', 10 );

	}

	/**
	 * Test whether the current version of PHP or WordPress is supported.
	 *
	 * @since 4.10
	 *
	 * @param string $system Which system to test the version of such as 'php' or 'wordpress'.
	 *
	 * @return boolean Whether the current version of PHP or WordPress is supported.
	 */
	public function supported_version( $system ) {
		if ( $supported = wp_cache_get( $system, 'tribe_version_test' ) ) {
			return $supported;
		}

		switch ( strtolower( $system ) ) {
			case 'wordpress' :
				$supported = version_compare( get_bloginfo( 'version' ), $this->min_wordpress, '>=' );
				break;
			case 'php' :
				$supported = version_compare( phpversion(), $this->min_php, '>=' );
				break;
		}

		/**
		 * Filter whether the current version of PHP or WordPress is supported.
		 *
		 * @since 4.10
		 *
		 * @param boolean $supported Whether the current version of PHP or WordPress is supported.
		 * @param string  $system    Which system to test the version of such as 'php' or 'wordpress'.
		 */
		$supported = apply_filters( 'tribe_tickets_supported_system_version', $supported, $system );

		wp_cache_set( $system, $supported, 'tribe_version_test' );

		return $supported;
	}

	/**
	 * Display a WordPress or PHP incompatibility error.
	 *
	 * @since 4.10
	 */
	public function not_supported_error() {
		if ( ! self::supported_version( 'wordpress' ) ) {
			echo '<div class="error"><p>' . esc_html( sprintf( __( 'Sorry, Event Tickets requires WordPress %s or higher. Please upgrade your WordPress install.', 'event-tickets' ), $this->min_wordpress ) ) . '</p></div>';
		}

		if ( ! self::supported_version( 'php' ) ) {
			echo '<div class="error"><p>' . esc_html( sprintf( __( 'Sorry, Event Tickets requires PHP %s or higher. Talk to your Web host about moving you to a newer version of PHP.', 'event-tickets' ), $this->min_php ) ) . '</p></div>';
		}
	}

	/**
	 * Set the Event Tickets version in the options table if it's not already set.
	 */
	public function maybe_set_et_version() {
		if ( version_compare( Tribe__Settings_Manager::get_option( $this->latest_version_slug ), self::VERSION, '<' ) ) {
			$previous_versions = Tribe__Settings_Manager::get_option( $this->version_history_slug )
				? Tribe__Settings_Manager::get_option( $this->version_history_slug )
				: [];

			$previous_versions[] = Tribe__Settings_Manager::get_option( $this->latest_version_slug )
				? Tribe__Settings_Manager::get_option( $this->latest_version_slug )
				: '0';

			Tribe__Settings_Manager::set_option( $this->version_history_slug, $previous_versions );
			Tribe__Settings_Manager::set_option( $this->latest_version_slug, self::VERSION );
		}
	}

	/**
	 * Sets up autoloading
	 */
	protected function init_autoloading() {
		$autoloader = $this->get_autoloader_instance();
		$this->register_plugin_autoload_paths();

		require_once $this->plugin_path . 'src/template-tags/tickets.php';
		require_once $this->plugin_path . 'src/template-tags/commerce.php';

		// deprecated classes are registered in a class to path fashion
		foreach ( glob( $this->plugin_path . 'src/deprecated/*.php' ) as $file ) {
			$class_name = str_replace( '.php', '', basename( $file ) );
			$autoloader->register_class( $class_name, $file );
		}

		$autoloader->register_autoloader();
	}

	/**
	 * Set up hooks for this class.
	 */
	public function hooks() {
		add_action( 'tribe_load_text_domains', [ $this, 'load_text_domain' ] );

		add_action( 'init', [ $this, 'init' ] );

		// Connect upgrade script.
		add_action( 'init', [ $this, 'run_updates' ], 0, 0 );

		add_filter( 'tribe_post_types', [ $this, 'inject_post_types' ] );

		// Setup Help Tab texting.
		add_action( 'tribe_help_pre_get_sections', [ $this, 'add_help_section_support_content' ] );
		add_action( 'tribe_help_pre_get_sections', [ $this, 'add_help_section_featured_content' ] );
		add_action( 'tribe_help_pre_get_sections', [ $this, 'add_help_section_extra_content' ] );
		add_filter( 'tribe_support_registered_template_systems', [ $this, 'add_template_updates_check' ] );
		add_action( 'tec_tickets_fully_loaded', [ 'Tribe__Support', 'getInstance' ] );

		// Setup Front End Display.
		add_action( 'tribe_events_inside_cost', 'tribe_tickets_buy_button', 10, 0 );

		// Hook to oEmbeds.
		add_action( 'tribe_events_embed_after_the_cost_value', [ $this, 'inject_buy_button_into_oembed' ] );
		add_action( 'embed_head', [ $this, 'embed_head' ] );

		// Attendee screen enhancements.
		add_action( 'tribe_events_tickets_attendees_event_details_top', [ $this, 'setup_attendance_totals' ], 20 );

		// CSV Import options.
		if ( class_exists( 'Tribe__Events__Main', false ) ) {
			add_filter( 'tribe_events_import_options_rows', [ Tribe__Tickets__CSV_Importer__Rows::instance(), 'filter_import_options_rows' ] );
			add_filter( 'tribe_aggregator_csv_post_types', [ Tribe__Tickets__CSV_Importer__Rows::instance(), 'filter_csv_post_types' ] );
			add_filter( 'tribe_aggregator_csv_column_mapping', [ Tribe__Tickets__CSV_Importer__Column_Names::instance(), 'filter_rsvp_column_mapping' ] );
			add_filter( 'tribe_event_import_rsvp_tickets_column_names', [ Tribe__Tickets__CSV_Importer__Column_Names::instance(), 'filter_rsvp_column_names' ] );

			add_action( 'tribe_tickets_ticket_deleted', [ 'Tribe__Tickets__Attendance', 'delete_attendees_caches' ] );
		}

		// Add the RSVP importer if the File_Importer class is present.
		if ( class_exists( 'Tribe__Events__Importer__File_Importer' ) ) {
			add_filter( 'tribe_events_import_rsvp_tickets_importer', [ 'Tribe__Tickets__CSV_Importer__RSVP_Importer', 'instance' ], 10, 2 );
		}

		/**
		 * Load our assets.
		 *
		 * @see \Tribe__Tickets__Assets::enqueue_scripts()
		 * @see \Tribe__Tickets__Assets::admin_enqueue_scripts()
		 * @see \Tribe__Tickets__Assets::enqueue_editor_scripts()
		 * @see \Tribe__Tickets__Assets::add_data_strings()
		 */
		add_action( 'init', tribe_callback( 'tickets.assets', 'enqueue_scripts' ) );
		add_action( 'init', tribe_callback( 'tickets.assets', 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', tribe_callback( 'tickets.assets', 'enqueue_editor_scripts' ) );
		add_filter( 'tribe_asset_data_add_object_tribe_l10n_datatables', tribe_callback( 'tickets.assets', 'add_data_strings' ) );

		// Redirections.
		add_action( 'wp_loaded', tribe_callback( 'tickets.redirections', 'maybe_redirect' ) );

		// Cart handling.
		add_action( 'init', tribe_callback( 'tickets.commerce.cart', 'hook' ) );

		// Theme Compatibility.
		add_filter( 'body_class', [ tribe( Tribe__Tickets__Theme_Compatibility::class ), 'add_body_classes' ], 55 );
	}

	/**
	 * Append the text about Event Tickets to the support section on the Help page
	 *
	 * @filter "tribe_help_pre_get_sections"
	 * @param Tribe__Admin__Help_Page $help The Help Page Instance
	 * @return void
	 */
	public function add_help_section_support_content( $help ) {
		$help->add_section_content( 'support', '<strong>' . esc_html__( 'Support for Event Tickets', 'event-tickets' ) . '</strong>', 20 );
		$help->add_section_content( 'support', [
			'<strong><a href="https://evnt.is/18ne" target="_blank">' . esc_html__( 'Settings overview', 'event-tickets' ) . '</a></strong>',
			'<strong><a href="https://evnt.is/18nf" target="_blank">' . esc_html__( 'Features overview', 'event-tickets' ) . '</a></strong>',
			'<strong><a href="https://evnt.is/18jb" target="_blank">' . esc_html__( 'Troubleshooting common problems', 'event-tickets' ) . '</a></strong>',
			'<strong><a href="https://evnt.is/18ng" target="_blank">' . esc_html__( 'Customizing Event Tickets', 'event-tickets' ) . '</a></strong>',
		], 20 );
	}

	/**
	 * Append the text about Event Tickets to the Feature box section on the Help page
	 *
	 * @filter "tribe_help_pre_get_sections"
	 * @param Tribe__Admin__Help_Page $help The Help Page Instance
	 * @return void
	 */
	public function add_help_section_featured_content( $help ) {
		// If The Events Calendar is active dont add
		if ( $help->is_active( 'the-events-calendar', true ) ) {
			return;
		}

		$link = '<a href="https://evnt.is/18nd" target="_blank">' . esc_html__( 'New User Primer', 'event-tickets' ) . '</a>';

		$help->add_section_content( 'feature-box', sprintf( _x( 'We are committed to helping you sell %1$s for your event. Check out our handy %2$s to get started.', 'help feature box section', 'event-tickets' ), tribe_get_ticket_label_plural_lowercase( 'help_feature_box_section' ), $link ), 20 );
	}

	/**
	 * Append the text about Event Tickets to the Extra Help section on the Help page
	 *
	 * @filter "tribe_help_pre_get_sections"
	 * @param Tribe__Admin__Help_Page $help The Help Page Instance
	 * @return void
	 */
	public function add_help_section_extra_content( $help ) {
		if ( ! $help->is_active( [ 'events-calendar-pro', 'event-tickets-plus' ] ) && $help->is_active( 'the-events-calendar' ) ) {
			// We just skip because it's treated on TEC
			return;
		} elseif ( ! $help->is_active( 'the-events-calendar' ) ) {
			if ( ! $help->is_active( 'event-tickets-plus' ) ) {

				$link = '<a href="https://wordpress.org/support/plugin/event-tickets/" target="_blank">' . esc_html__( 'open-source forum on WordPress.org', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'If you have tried the above steps and are still having trouble, you can post a new thread to our %s. Our support staff monitors these forums once a week and would be happy to assist you there.', 'event-tickets' ), $link ), 20 );

				$link_forum = '<a href="https://evnt.is/4w/" target="_blank">' . esc_html__( 'premium support on our website', 'event-tickets' ) . '</a>';
				$link_plus = '<a href="https://evnt.is/18ni" target="_blank">' . esc_html__( 'Event Tickets Plus', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'Looking for more immediate support? We offer %1$s with the purchase of any of our premium plugins (like %2$s). Pick up a license and you can post there directly and expect a response within 24-48 hours during weekdays.', 'event-tickets' ), $link_forum, $link_plus ), 20 );

				$link = '<a href="https://evnt.is/4w/" target="_blank">' . esc_html__( 'post a thread', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'Already have Event Tickets Plus? You can %s in our premium support forums. Our support team monitors the forums and will respond to your thread within 24-48 hours (during the week).', 'event-tickets' ), $link ), 20 );

			}  else {

				$link = '<a href="https://evnt.is/4w/" target="_blank">' . esc_html__( 'post a thread', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'If you have a valid license for one of our paid plugins, you can %s in our premium support forums. Our support team monitors the forums and will respond to your thread within 24-48 hours (during the week).', 'event-tickets' ), $link ), 20 );

			}
		}
	}

	/**
	 * Register Event Tickets with the template update checker.
	 *
	 * @since 5.0.3 Updated template structure.
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function add_template_updates_check( $plugins ) {
		$plugins[ __( 'Event Tickets', 'event-tickets' ) ] = [
			self::VERSION,
			$this->plugin_path . 'src/views',
			trailingslashit( get_stylesheet_directory() ) . 'tribe/tickets',
		];

		return $plugins;
	}

	/**
	 * Load the Event Tickets text domain after Tribe Common's.
	 *
	 * @since 4.12.0
	 *
	 * @return bool
	 */
	public function load_text_domain() {
		return Tribe__Main::instance( $this )->load_text_domain( 'event-tickets', $this->plugin_dir . 'lang/' );
	}

	/**
	 * Hooked to the init action
	 */
	public function init() {
		// Provide continued support for legacy ticketing modules.
		$this->legacy_provider_support = new Tribe__Tickets__Legacy_Provider_Support;
		$this->settings_tab();
		$this->tickets_view();
		Tribe__Credits::init();
		$this->maybe_set_et_version();
		$this->maybe_set_options_for_old_installs();

	}

	/**
	 * Allows us to set options based on installed version.
	 * Also a good place for things that need to be changed
	 * or set if they are missing (like meta keys).
	 *
	 * @since 4.11.0
	 */
	public function maybe_set_options_for_old_installs() {
		/**
		 * This Try/Catch is present to deal with a problem on Autoloading from version 5.1.0 ET+ with ET 5.0.3.
		 *
		 * @todo Needs to be revised once proper autoloading rules are done for Common, ET and ET+.
		 */
		try {
			/** @var \Tribe__Tickets__Attendee_Registration__Main $ar_reg */
			$ar_reg = tribe( 'tickets.attendee_registration' );
		} catch ( \Exception $exception ) {
			return;
		}

		// If the (bool) option is not set, and this install predated the modal, let's set the option to false.
		$modal_option = $ar_reg->is_modal_enabled();

		if ( ! $modal_option && $modal_option !== false ) {
			$modal_version_check = tribe_installed_before( Tribe__Tickets__Main::instance(), '4.11.0' );
			if ( $modal_version_check ) {
				/** @var $settings_manager Tribe__Settings_Manager */
				$settings_manager = tribe( 'settings.manager' );

				$settings_manager::set_option( 'ticket-attendee-modal', false );
			}
		}
	}

	/**
	 * rsvp ticket object accessor
	 */
	public function rsvp() {
		return tribe( 'tickets.rsvp' );
	}

	/**
	 * Creates the Tickets FrontEnd facing View class
	 *
	 * This will happen on `plugins_loaded` by default
	 *
	 * @return Tribe__Tickets__Tickets_View
	 */
	public function tickets_view() {
		return Tribe__Tickets__Tickets_View::hook();
	}

	/**
	 * Default attendee list shortcode handler.
	 *
	 * @return Tribe__Tickets__Shortcodes__User_Event_Confirmation_List
	 */
	public function user_event_confirmation_list_shortcode() {
		if ( empty( $this->user_event_confirmation_list_shortcode ) ) {
			$this->user_event_confirmation_list_shortcode = new Tribe__Tickets__Shortcodes__User_Event_Confirmation_List;
		}

		return $this->user_event_confirmation_list_shortcode;
	}

	/**
	 * @return Tribe__Tickets__Admin__Move_Tickets
	 */
	public function move_tickets() {
		if ( empty( $this->move_tickets ) ) {
			$this->move_tickets = new Tribe__Tickets__Admin__Move_Tickets;
			$this->move_tickets->setup();
		}

		return $this->move_tickets;
	}

	/**
	 * @return Tribe__Tickets__Admin__Move_Ticket_Types
	 */
	public function move_ticket_types() {
		if ( empty( $this->move_ticket_types ) ) {
			$this->move_ticket_types = new Tribe__Tickets__Admin__Move_Ticket_Types;
			$this->move_ticket_types->setup();
		}

		return $this->move_ticket_types;
	}

	/**
	 * @deprecated 5.23.0
	 *
	 * @return Tribe__Admin__Activation_Page
	 */
	public function activation_page() {
		_deprecated_function( __METHOD__, '5.23.0', 'Now handled by TEC\Tickets\Admin\Onboarding\Controller' );

		if ( empty( $this->activation_page ) ) {
			$this->activation_page = new Tribe__Admin__Activation_Page( [
				'slug'                  => 'event-tickets',
				'admin_page'            => 'tickets_page_tec-tickets-settings',
				'admin_url'             => tribe( Settings::class )->get_url(),
				'version'               => self::VERSION,
				'activation_transient'  => Tickets_Landing_Page::ACTIVATION_REDIRECT_OPTION,
				'plugin_path'           => $this->plugin_dir . 'event-tickets.php',
				'version_history_slug'  => $this->version_history_slug,
				'welcome_page_title'    => esc_html__( 'Welcome to Event Tickets!', 'event-tickets' ),
				'welcome_page_template' => $this->plugin_path . 'src/admin-views/admin-welcome-message.php',
			] );

			tec_asset(
				$this,
				'tribe-tickets-welcome-message',
				'admin/welcome-message.js',
				[ 'jquery' ],
				'admin_enqueue_scripts',
				[
					'conditionals' => [ $this->activation_page, 'is_welcome_page' ],
				]
			);
		}

		return $this->activation_page;
	}

	/**
	 * Adds RSVP attendance totals to the summary box of the attendance
	 * screen.
	 *
	 * Expects to fire during 'tribe_tickets_attendees_page_inside', ie
	 * before the attendee screen is rendered.
	 *
	 * @since 4.2.4
	 * @since 5.8.2 Add the `$event_id` parameter.
	 *
	 * @param int|bool $event_id The post ID to set up attendance totals for.
	 */
	public function setup_attendance_totals( $event_id = null ) {
		$this->attendance_totals( $event_id )->integrate_with_attendee_screen();
	}

	/**
	 * Returns the attendance totals object.
	 *
	 * @since 4.2.4
	 * @since 5.8.2 Added the `$event_id` parameter.
	 *
	 * @param int|null $event_id The post ID to set up attendance totals for.
	 *
	 * @return Tribe__Tickets__Attendance_Totals The attendance totals object.
	 */
	public function attendance_totals( $event_id = null ) {
		if ( empty( $this->attendance_totals ) ) {
			$this->attendance_totals = new Tribe__Tickets__Attendance_Totals;
		}

		$this->attendance_totals->set_event_id( $event_id );

		return $this->attendance_totals;
	}

	/**
	 * Provides the CSS version number for CSS files
	 *
	 * @return string
	 */
	public function css_version() {
		static $version;

		if ( ! $version ) {
			$version = apply_filters( 'tribe_tickets_css_version', self::VERSION );
		}

		return $version;
	}

	/**
	 * Provides the JS version number for JS scripts
	 *
	 * @return string
	 */
	public function js_version() {
		static $version;

		if ( ! $version ) {
			$version = apply_filters( 'tribe_tickets_js_version', self::VERSION );
		}

		return $version;
	}

	/**
	 * Settings page object accessor.
	 *
	 * @return \Tribe\Tickets\Admin\Settings
	 */
	public function settings_tab() {
		return tribe( \Tribe\Tickets\Admin\Settings::class );
	}

	/**
	 * Settings page object accessor.
	 *
	 * @since 5.4.0
	 *
	 * @return \Tribe\Tickets\Admin\Settings
	 */
	public function settings() {
		return $this->settings_tab();
	}

	/**
	 * Returns the supported post types for tickets
	 *
	 * @return array<string>
	 */
	public function post_types() {
		$options = (array) get_option( Tribe__Main::OPTIONNAME, [] );

		// If the ticket-enabled-post-types index has never been set, default it to tribe_events and page.
		if ( ! array_key_exists( 'ticket-enabled-post-types', $options ) ) {
			$defaults = [
				'tribe_events',
				'page',
			];

			$options['ticket-enabled-post-types'] = $defaults;

			tribe_update_option( 'ticket-enabled-post-types', $defaults );
		}

		// Remove WooCommerce Product and EDD post types to prevent recursion fatal error on save.
		$filtered_post_types = array_diff( (array) $options['ticket-enabled-post-types'], [ 'product', 'download' ] );

		/**
		 * Filters the list of post types that support tickets
		 *
		 * @param array $post_types Array of post types
		 */
		return apply_filters( 'tribe_tickets_post_types', $filtered_post_types );
	}

	/**
	 * Injects post types into the tribe-common post_types array
	 */
	public function inject_post_types( $post_types ) {
		$post_types = array_merge( $post_types, $this->post_types() );
		return $post_types;
	}

	/**
	 * Injects a buy/RSVP button into oEmbeds for events when necessary
	 */
	public function inject_buy_button_into_oembed() {
		$event_id = get_the_ID();

		if ( ! tribe_events_has_tickets( $event_id ) ) {
			return;
		}

		$tickets      = Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );
		$has_non_rsvp = false;
		$available    = false;

		foreach ( $tickets as $ticket ) {
			if ( 'Tribe__Tickets__RSVP' !== $ticket->provider_class ) {
				$has_non_rsvp = true;
			}

			if (
				$ticket->date_in_range()
				&& $ticket->is_in_stock()
			) {
				$available = true;
			}
		}

		// if there aren't any tickets available, bail
		if ( ! $available ) {
			return;
		}

		$button_text = $has_non_rsvp ? __( 'Buy', 'event-tickets' ) : tribe_get_rsvp_label_singular( 'button_text' );
		/**
		 * Filters the text that appears in the buy/rsvp button on event oEmbeds
		 *
		 * @var string The button text
		 * @var int Event ID
		 */
		$button_text = apply_filters( 'event_tickets_embed_buy_button_text', $button_text, $event_id );

		ob_start();
		?>
		<a class="tribe-event-buy" href="<?php echo esc_url( tribe_get_event_link() ); ?>" title="<?php the_title_attribute() ?>" rel="bookmark"><?php echo esc_html( $button_text ); ?></a>
		<?php
		$buy_button = ob_get_clean();

		/**
		 * Filters the buy button that appears on event oEmbeds
		 *
		 * @var string The button markup
		 * @var int Event ID
		 */
		echo apply_filters( 'event_tickets_embed_buy_button', $buy_button, $event_id );
	}

	/**
	 * Adds content to the embed head tag
	 *
	 * The embed header DOES NOT have wp_head() executed inside of it. Instead, any scripts/styles
	 * are explicitly output
	 */
	public function embed_head() {
		$css_path = Tribe__Assets::maybe_get_min_file( $this->plugin_url . 'src/resources/css/tickets-embed.css' );
		$css_path = add_query_arg( 'ver', self::VERSION, $css_path );
		?>
		<link rel="stylesheet" id="tribe-tickets-embed-css" href="<?php echo esc_url( $css_path ); ?>" type="text/css" media="all">
		<?php
	}

	/**
	 * Make necessary database updates on admin_init
	 *
	 * @since 4.7.1
	 *
	 */
	public function run_updates() {
		if ( ! class_exists( 'Tribe__Updater' ) ) {
			return;
		}

		$updater = new Tribe__Tickets__Updater( self::VERSION );
		if ( $updater->update_required() ) {
			$updater->do_updates();
		}
	}

	/**
	 * Returns the autoloader singleton instance to use in a context-aware manner.
	 *
	 * @since 4.10.6
	 *
	 * @return \Tribe__Autoloader Teh singleton common Autoloader instance.
	 */
	public function get_autoloader_instance() {
		if ( ! class_exists( 'Tribe__Autoloader' ) ) {
			require_once $GLOBALS['tribe-common-info']['dir'] . '/Autoloader.php';

			Tribe__Autoloader::instance()->register_prefixes( [
				'Tribe__' => $GLOBALS['tribe-common-info']['dir'],
			] );
		}

		return Tribe__Autoloader::instance();
	}

	/**
	 * Registers the plugin autoload paths in the Common Autoloader instance.
	 *
	 * @since 4.10.6
	 */
	public function register_plugin_autoload_paths() {
		$prefixes = [
			'Tribe__Tickets__' => $this->plugin_path . 'src/Tribe',
		];

		$this->get_autoloader_instance()->register_prefixes( $prefixes );
	}
}
