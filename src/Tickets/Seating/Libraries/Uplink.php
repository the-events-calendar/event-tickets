<?php
/**
 * The Controller to set up the Uplink library.
 *
 * @since 5.16.0
 * @package TEC\Tickets\Libraries\Uplink
 */

namespace TEC\Tickets\Seating\Libraries;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Uplink\Register;
use Tribe__Tickets__Main as Main;

/**
 * Controller for setting up the stellarwp/uplink library.
 *
 * @since 5.16.0
 *
 * @package TEC\Tickets\Libraries\Uplink
 */
class Uplink extends Controller_Contract {
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
	 * @var Main
	 */
	protected Main $et_main;

	/**
	 * Register the controller.
	 *
	 * @since 5.16.0
	 */
	public function do_register(): void {
		$this->et_main = tribe( 'tickets.main' );

		add_action( 'init', [ $this, 'register_plugin' ] );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'init', [ $this, 'register_plugin' ] );
	}

	/**
	 * Register the plugin in the uplink library.
	 *
	 * @since 5.16.0
	 *
	 * @return void
	 */
	public function register_plugin(): void {
		Register::plugin(
			$this->et_slr_plugin_slug,
			$this->et_slr_plugin_name,
			Main::VERSION,
			"{$this->et_main->plugin_dir}/event-tickets.php",
			Main::class,
			null,
			true
		);
	}
}
