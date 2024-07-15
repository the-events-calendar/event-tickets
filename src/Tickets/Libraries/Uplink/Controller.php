<?php
/**
 * The Controller to set up the Uplink library.
 *
 * @since   TBD
 * @package TEC\Tickets\Libraries\Uplink
 */

namespace TEC\Tickets\Libraries\Uplink;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Uplink\Register;
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
	protected string $et_slr_plugin_slug = 'tec-seating';

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	protected string $et_slr_plugin_name = 'Seat Layouts & Reservations';

	/**
	 * Main plugin object.
	 *
	 * @var object
	 */
	protected object $et_main;

	/**
	 * Register the controller.
	 *
	 * @since TBD
	 */
	public function do_register(): void {
		$this->et_main = tribe( 'tickets.main' );
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
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since TBD
	 */
	public function remove_actions(): void {
		remove_action( 'init', [ $this, 'register_plugin' ] );
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
			"{$this->et_main->plugin_path}",
			$this->et_main,
			null,
			true
		);
	}
}
