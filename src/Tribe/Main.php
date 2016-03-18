<?php

class Tribe__Tickets__Main {
	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;

	/**
	 * Current version of this plugin
	 */
	const VERSION = '4.1.1';

	/**
	 * Min required The Events Calendar version
	 */
	const MIN_TEC_VERSION = '3.12.4';

	/**
	 * Name of the provider
	 * @var
	 */
	public $plugin_name;

	/**
	 * Directory of the plugin
	 * @var
	 */
	public $plugin_dir;

	/**
	 * Path of the plugin
	 * @var
	 */
	public $plugin_path;

	/**
	 * URL of the plugin
	 * @var
	 */
	public $plugin_url;

	/**
	 * @var Tribe__Tickets__Legacy_Provider_Support
	 */
	public $legacy_provider_support;

	private $has_initialized = false;

	/**
	 * Get (and instantiate, if necessary) the instance of the class
	 *
	 * @static
	 * @return Tribe__Tickets__Woo__Main
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		/* Set up some parent's vars */
		$this->plugin_name = 'Tickets';
		$this->plugin_slug = 'tickets';
		$this->plugin_path = trailingslashit( EVENT_TICKETS_DIR );
		$this->plugin_dir = trailingslashit( basename( $this->plugin_path ) );

		$dir_prefix = '';

		if ( false !== strstr( EVENT_TICKETS_DIR, '/vendor/' ) ) {
			$dir_prefix = basename( dirname( dirname( EVENT_TICKETS_DIR ) ) ) . '/vendor/';
		}

		$this->plugin_url = trailingslashit( plugins_url( $dir_prefix . $this->plugin_dir ) );

		$this->maybe_set_common_lib_info();

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 0 );
	}

	/**
	 * Finalize the initialization of this plugin
	 */
	public function plugins_loaded() {
		// It's possible we'll have initialized already (if the plugin has been embedded as a vendor lib
		// within another plugin, for example) in which case we need not repeat the process
		if ( $this->has_initialized ) {
			return;
		}

		if (
			class_exists( 'TribeEvents', false )
			|| ( class_exists( 'Tribe__Events__Main' ) && ! version_compare( Tribe__Events__Main::VERSION, self::MIN_TEC_VERSION, '>=' ) )
		) {
			add_action( 'admin_notices', array( $this, 'tec_compatibility_notice' ) );
			return;
		}

		$this->init_autoloading();

		// initialize the common libraries
		$this->common();

		load_plugin_textdomain( 'event-tickets', false, $this->plugin_dir . 'lang/' );

		$this->hooks();

		$this->has_initialized = true;

		// set up the RSVP object
		$this->rsvp();
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

		$upgrade_path      = wp_nonce_url(
			add_query_arg(
				array(
					'action' => 'upgrade-plugin',
					'plugin' => $plugin_short_path,
				), get_admin_url() . 'update.php'
			), 'upgrade-plugin_' . $plugin_short_path
		);
		$output = '<div class="error">';
		$output .= '<p>' . sprintf( __( 'When The Events Calendar and Event Tickets are both activated, The Events Calendar must be running version %1$s or greater. Please %2$supdate now.%3$s', 'event-tickets' ), self::MIN_TEC_VERSION, '<a href="' . esc_url( $upgrade_path ) . '">', '</a>' ) . '</p>';
		$output .= '</div>';

		echo $output;
	}

	public function maybe_set_common_lib_info() {
		$common_version = file_get_contents( $this->plugin_path . 'common/src/Tribe/Main.php' );

		// if there isn't a tribe-common version, bail
		if ( ! preg_match( "/const\s+VERSION\s*=\s*'([^']+)'/m", $common_version, $matches ) ) {
			add_action( 'admin_head', array( $this, 'missing_common_libs' ) );

			return;
		}

		$common_version = $matches[1];

		if ( empty( $GLOBALS['tribe-common-info'] ) ) {
			$GLOBALS['tribe-common-info'] = array(
				'dir' => "{$this->plugin_path}common/src/Tribe",
				'version' => $common_version,
			);
		} elseif ( 1 == version_compare( $GLOBALS['tribe-common-info']['version'], $common_version, '<' ) ) {
			$GLOBALS['tribe-common-info'] = array(
				'dir' => "{$this->plugin_path}common/src/Tribe",
				'version' => $common_version,
			);
		}
	}

	/**
	 * Common library object accessor method
	 */
	public function common() {
		static $common;

		if ( ! $common ) {
			$common = new Tribe__Main( $this );
		}

		return $common;
	}

	/**
	 * Sets up autoloading
	 */
	protected function init_autoloading() {
		$prefixes = array(
			'Tribe__Tickets__' => $this->plugin_path . 'src/Tribe',
		);

		if ( ! class_exists( 'Tribe__Autoloader' ) ) {
			require_once( $GLOBALS['tribe-common-info']['dir'] . '/Autoloader.php' );

			$prefixes['Tribe__'] = $GLOBALS['tribe-common-info']['dir'];
		}

		$autoloader = Tribe__Autoloader::instance();
		$autoloader->register_prefixes( $prefixes );

		require_once $this->plugin_path . 'src/template-tags/tickets.php';

		// deprecated classes are registered in a class to path fashion
		foreach ( glob( $this->plugin_path . '{common/src,src}/deprecated/*.php', GLOB_BRACE ) as $file ) {
			$class_name = str_replace( '.php', '', basename( $file ) );
			$autoloader->register_class( $class_name, $file );
		}

		$autoloader->register_autoloader();
	}

	/**
	 * set up hooks for this class
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'add_meta_boxes', array( 'Tribe__Tickets__Metabox', 'maybe_add_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( 'Tribe__Tickets__Metabox', 'add_admin_scripts' ) );
		add_filter( 'tribe_post_types', array( $this, 'inject_post_types' ) );

		// Setup Help Tab texting
		add_action( 'tribe_help_pre_get_sections', array( $this, 'add_help_section_support_content' ) );
		add_action( 'tribe_help_pre_get_sections', array( $this, 'add_help_section_featured_content' ) );
		add_action( 'tribe_help_pre_get_sections', array( $this, 'add_help_section_extra_content' ) );
		add_filter( 'tribe_support_registered_template_systems', array( $this, 'add_template_updates_check' ) );
		add_action( 'plugins_loaded', array( 'Tribe__Support', 'getInstance' ) );
		add_action( 'tribe_events_single_event_after_the_meta', array( $this, 'add_linking_archor' ), 5 );

	}

	/**
	 * Add an Anchor for users to be able to link to
	 * The height is to make sure it links on all browsers
	 *
	 * @return void
	 */
	public function add_linking_archor() {
		echo '<div id="buy-tickets" style="height: 1px;"></div>';
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
		$help->add_section_content( 'support', array(
			'<strong><a href="http://m.tri.be/18ne" target="_blank">' . esc_html__( 'Settings overview', 'event-tickets' ) . '</a></strong>',
			'<strong><a href="http://m.tri.be/18nf" target="_blank">' . esc_html__( 'Features overview', 'event-tickets' ) . '</a></strong>',
			'<strong><a href="http://m.tri.be/18jb" target="_blank">' . esc_html__( 'Troubleshooting common problems', 'event-tickets' ) . '</a></strong>',
			'<strong><a href="http://m.tri.be/18ng" target="_blank">' . esc_html__( 'Customizing Event Tickets', 'event-tickets' ) . '</a></strong>',
		), 20 );
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

		$link = '<a href="http://m.tri.be/18nd" target="_blank">' . esc_html__( 'New User Primer', 'event-tickets' ) . '</a>';

		$help->add_section_content( 'feature-box', sprintf( __( 'We are committed to helping you sell tickets for your event. Check out our handy %s to get started.', 'event-tickets' ), $link ), 20 );
	}


	/**
	 * Append the text about Event Tickets to the Extra Help section on the Help page
	 *
	 * @filter "tribe_help_pre_get_sections"
	 * @param Tribe__Admin__Help_Page $help The Help Page Instance
	 * @return void
	 */
	public function add_help_section_extra_content( $help ) {
		if ( ! $help->is_active( array( 'events-calendar-pro', 'event-tickets-plus' ) ) && $help->is_active( 'the-events-calendar' ) ) {
			// We just skip because it's treated on TEC
			return;
		} elseif ( ! $help->is_active( 'the-events-calendar' ) ) {
			if ( ! $help->is_active( 'event-tickets-plus' ) ) {

				$link = '<a href="https://wordpress.org/support/plugin/event-tickets/" target="_blank">' . esc_html__( 'open-source forum on WordPress.org', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'If you have tried the above steps and are still having trouble, you can post a new thread to our %s. Our support staff monitors these forums once a week and would be happy to assist you there.', 'event-tickets' ), $link ), 20 );

				$link_forum = '<a href="http://m.tri.be/4w/" target="_blank">' . esc_html__( 'premium support on our website', 'event-tickets' ) . '</a>';
				$link_plus = '<a href="http://m.tri.be/18ni" target="_blank">' . esc_html__( 'Events Tickets Plus', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'Looking for more immediate support? We offer %1$s with the purchase of any of our premium plugins (like %2$s). Pick up a license and you can post there directly and expect a response within 24-48 hours during weekdays.', 'event-tickets' ), $link_forum, $link_plus ), 20 );

				$link = '<a href="http://m.tri.be/4w/" target="_blank">' . esc_html__( 'post a thread', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'Already have Events Tickets Plus? You can %s in our premium support forums. Our support team monitors the forums and will respond to your thread within 24-48 hours (during the week).', 'event-tickets' ), $link ), 20 );

			}  else {

				$link = '<a href="http://m.tri.be/4w/" target="_blank">' . esc_html__( 'post a thread', 'event-tickets' ) . '</a>';
				$help->add_section_content( 'extra-help', sprintf( __( 'If you have a valid license for one of our paid plugins, you can %s in our premium support forums. Our support team monitors the forums and will respond to your thread within 24-48 hours (during the week).', 'event-tickets' ), $link ), 20 );

			}
		}
	}

	/**
	 * Register Event Tickets with the template update checker.
	 *
	 * @param array $plugins
	 *
	 * @return array
	 */
	public function add_template_updates_check( $plugins ) {
		$plugins[ __( 'Event Tickets', 'event-tickets' ) ] = array(
			self::VERSION,
			$this->plugin_path . 'src/views/tickets',
			trailingslashit( get_stylesheet_directory() ) . 'tribe-events/tickets'
		);

		return $plugins;
	}

	/**
	 * Hooked to the init action
	 */
	public function init() {
		// Provide continued support for legacy ticketing modules
		$this->legacy_provider_support = new Tribe__Tickets__Legacy_Provider_Support;

		$this->settings_tab();
	}

	/**
	 * rsvp ticket object accessor
	 */
	public function rsvp() {
		return Tribe__Tickets__RSVP::get_instance();
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
	 * settings page object accessor
	 */
	public function settings_tab() {
		static $settings;

		if ( ! $settings ) {
			$settings = new Tribe__Tickets__Admin__Ticket_Settings;
		}

		return $settings;
	}

	/**
	 * Returns the supported post types for tickets
	 */
	public function post_types() {
		$options = get_option( Tribe__Main::OPTIONNAME, array() );

		// if the ticket-enabled-post-types index has never been set, default it to tribe_events
		if ( ! array_key_exists( 'ticket-enabled-post-types', $options ) ) {
			$options['ticket-enabled-post-types'] = array(
				'tribe_events',
			);
		}

		/**
		 * Filters the list of post types that support tickets
		 *
		 * @param array $post_types Array of post types
		 */
		return apply_filters( 'tribe_tickets_post_types', (array) $options['ticket-enabled-post-types'] );
	}

	/**
	 * Injects post types into the tribe-common post_types array
	 */
	public function inject_post_types( $post_types ) {
		$post_types = array_merge( $post_types, $this->post_types() );
		return $post_types;
	}
}
