<?php


/**
 * Run schema updates on plugin activation or updates
 *
 * @since TBD
 *
 */
class Tribe__Tickets__Updater {

	protected $version_option = 'event-tickets-schema-version';
	protected $reset_version = '4.2'; // when a reset() is called, go to this version
	protected $current_version = 0;
	public $capabilities;

	public function __construct( $current_version ) {
		$this->current_version = $current_version;
	}

	/**
	 * We've had problems with the notoptions and
	 * alloptions caches getting out of sync with the DB,
	 * forcing an eternal update cycle
	 *
	 */
	protected function clear_option_caches() {
		wp_cache_delete( 'notoptions', 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	}

	public function do_updates() {
		$this->clear_option_caches();
		$updates = $this->get_update_callbacks();
		uksort( $updates, 'version_compare' );

		try {
			foreach ( $updates as $version => $callback ) {

				if ( version_compare( $version, $this->current_version, '<=' ) && $this->is_version_in_db_less_than( $version ) ) {
					call_user_func( $callback );
				}
			}

			foreach ( $this->get_constant_update_callbacks() as $callback )  {
				call_user_func( $callback );
			}

			$this->update_version_option( $this->current_version );
		} catch ( Exception $e ) {
			// fail silently, but it should try again next time
		}
	}

	public function update_version_option( $new_version ) {
		Tribe__Settings_Manager::set_option( $this->version_option, $new_version );
	}

	/**
	 * Returns an array of callbacks with version strings as keys.
	 * Any key higher than the version recorded in the DB
	 * and lower than $this->current_version will have its
	 * callback called.
	 *
	 * @return array
	 */
	public function get_update_callbacks() {
		return array();
	}

	/**
	 * Returns an array of callbacks that should be called
	 * every time the version is updated
	 *
	 * @return array
	 */
	public function get_constant_update_callbacks() {
		return array(
			array( $this, 'flush_rewrites' ),
			array( $this, 'set_capabilities' ),
		);
	}

	public function get_version_from_db() {
		return Tribe__Settings_Manager::get_option( $this->version_option );
	}

	/**
	 * Returns true if the version in the DB is less than the provided version
	 *
	 * @return boolean
	 */
	public function is_version_in_db_less_than( $version ) {
		$version_in_db = $this->get_version_from_db();

		return ( version_compare( $version, $version_in_db ) > 0 );
	}


	/**
	 * Returns true if an update is required
	 *
	 * @return boolean
	 */
	public function update_required() {
		return $this->is_version_in_db_less_than( $this->current_version );
	}

	public function flush_rewrites() {
		// run after 'init' to ensure that all CPTs are registered
		add_action( 'wp_loaded', 'flush_rewrite_rules' );
	}

	public function set_capabilities() {
		$this->capabilities = new Tribe__Tickets__Capabilities();
		add_action( 'wp_loaded', array( $this->capabilities, 'set_initial_caps' ) );
		add_action( 'wp_loaded', array( $this, 'reload_current_user' ), 11, 0 );
	}

	/**
	 * Reset the $current_user global after capabilities have been changed
	 *
	 */
	public function reload_current_user() {
		global $current_user;
		if ( isset( $current_user ) && ( $current_user instanceof WP_User ) ) {
			$id = $current_user->ID;
			$current_user = null;
			wp_set_current_user( $id );
		}
	}

	/**
	 * Reset update flags. All updates past $this->reset_version will
	 * run again on the next page load
	 *
	 */
	public function reset() {
		$this->update_version_option( $this->reset_version );
	}

}
