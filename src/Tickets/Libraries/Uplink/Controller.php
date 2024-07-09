<?php

namespace TEC\Tickets\Libraries\Uplink;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Uplink\Register;
use function TEC\Common\StellarWP\Uplink\render_authorize_button;
use Tribe__Tickets__Main as Main;

/**
 * Controller for setting up the stellarwp/uplink library.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Libraries\Uplink
 */
class Controller extends Controller_Contract {
	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	protected $et_slr_plugin_slug = 'event-tickets-slr';

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected $et_slr_plugin_name = 'Seat Layouts & Reservations';

	/**
	 * Main plugin object.
	 *
	 * @var object
	 */
	protected $et_slr_main;

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->et_slr_main = tribe( 'tickets.main' );
		$this->add_actions();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
	}

	/**
	 * Add the action hooks.
	 *
	 * @since TBD
	 */
	public function add_actions(): void {
		add_action( 'init', [ $this, 'register_plugin' ] );
		add_action( 'tribe_license_fields', [ $this, 'add_slr_fields' ] );
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since TBD
	 */
	public function remove_actions(): void {
		remove_action( 'init', [ $this, 'register_plugin' ] );
		remove_action( 'tribe_license_fields', [ $this, 'add_slr_fields' ] );
	}

	/**
	 * Add license fields to the licenses tab.
	 *
	 * @since TBD
	 *
	 * @param array $licenses_tab License Tab array.
	 *
	 * @return array
	 */
	public function add_slr_fields( $licenses_tab ) {
		ob_start();
		render_authorize_button( $this->et_slr_plugin_slug );
		$button_html    = ob_get_clean();
		$licenses_tab[] = $button_html;
		return $licenses_tab;
	}

	/**
	 * Register the plugin in the uplink library.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_plugin(): void {
		Register::plugin(
			$this->et_slr_plugin_slug,
			$this->et_slr_plugin_name,
			Main::VERSION,
			"{$this->et_slr_main->plugin_path}",
			$this->et_slr_main
		);
	}
}
