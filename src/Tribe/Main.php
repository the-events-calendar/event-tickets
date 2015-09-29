<?php

class Tribe__Tickets__Main {
	/**
	 * Instance of this class for use as singleton
	 */
	private static $instance;

	/**
	 * Current version of this plugin
	 */
	const VERSION = '3.9.3';

	/**
	 * Min required The Events Calendar version
	 */
	const REQUIRED_TEC_VERSION = '3.9.2';

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
		$this->plugin_path = trailingslashit( TRIBE_TICKETS_DIR );
		$this->plugin_dir = trailingslashit( basename( $this->plugin_path ) );

		$dir_prefix = '';

		if ( false !== strstr( TRIBE_TICKETS_DIR, '/vendor/' ) ) {
			$dir_prefix = basename( dirname( dirname( TRIBE_TICKETS_DIR ) ) ) . '/vendor/';
		}

		$this->plugin_url = trailingslashit( plugins_url( $dir_prefix . $this->plugin_dir ) );

		$this->maybe_set_common_lib_info();

		$this->init_autoloading();

		// initialize the common libraries
		$this->common();

		load_plugin_textdomain( 'tribe-tickets', false, $this->plugin_dir . 'lang/' );

		$this->hooks();
	}

	public function maybe_set_common_lib_info() {
		$common_version = file_get_contents( $this->plugin_path . 'common/Tribe/Main.php' );

		// if there isn't a tribe-common version, bail
		if ( ! preg_match( "/const\s+VERSION\s*=\s*'([^']+)'/m", $common_version, $matches ) ) {
			add_action( 'admin_head', array( $this, 'missing_common_libs' ) );

			return;
		}

		$common_version = $matches[1];

		if ( empty( $GLOBALS['tribe-common-info'] ) ) {
			$GLOBALS['tribe-common-info'] = array(
				'dir' => "{$this->plugin_path}common/Tribe",
				'version' => $common_version,
			);
		} elseif ( 1 == version_compare( $GLOBALS['tribe-common-info']['version'], $common_version, '<' ) ) {
			$GLOBALS['tribe-common-info'] = array(
				'dir' => "{$this->plugin_path}common/Tribe",
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
		foreach ( glob( $this->plugin_path . '{common,src}/deprecated/*.php', GLOB_BRACE ) as $file ) {
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
	}

	/**
	 * Hooked to the init action
	 */
	public function init() {
		// set up the RSVP object
		$this->rsvp();

		$this->settings_tab();
	}

	/**
	 * rsvp ticket object accessor
	 */
	public function rsvp() {
		static $rsvp;

		if ( ! $rsvp ) {
			$rsvp = Tribe__Tickets__RSVP::get_instance();
		}

		return $rsvp;
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
		$options = get_option( Tribe__Main::OPTIONNAME );

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
