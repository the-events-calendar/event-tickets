<?php

class Tribe__Tickets__Main {
	/**
	 * Post types that tickets can be tied to
	 */
	private static $post_types = array( 'post' );

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
		$this->plugin_url = trailingslashit( plugins_url( $this->plugin_dir ) );

		$this->init_autoloading();

		load_plugin_textdomain( 'tribe-tickets', false, $this->plugin_dir . 'lang/' );

		$this->hooks();
	}

	/**
	 * Sets up autoloading
	 */
	protected function init_autoloading() {
		$autoloader = Tribe__Autoloader::instance();

		$autoloader->register_prefix( 'Tribe__Tickets__', $this->plugin_path . '/src/Tribe' );

		require_once $this->plugin_path . 'src/template-tags/tickets.php';

		// deprecated classes are registered in a class to path fashion
		foreach ( glob( $this->plugin_path . 'src/deprecated/*.php' ) as $file ) {
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
	}

	/**
	 * Hooked to the init action
	 */
	public function init() {
		// set up the RSVP object
		$this->rsvp();

		// if TEC is running, add event post types the supported post types list
		// @TODO: add settings page that allows users to select post types
		if ( class_exists( 'Tribe__Events__Main' ) ) {
			self::$post_types[] = Tribe__Events__Main::POSTTYPE;
		}
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
	 * Returns the supported post types for tickets
	 */
	public function post_types() {
		/**
		 * Filters the list of post types that support tickets
		 *
		 * @param array $post_types Array of post types
		 */
		return apply_filters( 'tribe_tickets_post_types', self::$post_types );
	}
}
