<?php
/**
 * The Seating Asset Group Path controller.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */

namespace TEC\Tickets\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Config;
use Tribe__Tickets__Main as Tickets_Plugin;

/**
 * Class Asset_Group_Path.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */
class Asset_Group_Path extends Controller_Contract {

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// Nothing to do here.
	}

	/**
	 * Registers the asset group path for seating
	 *
	 * @since 5.16.0
	 *
	 * @return void The asset group path is registered.
	 */
	protected function do_register(): void {
		Config::add_group_path( 'tec-seating', Tickets_Plugin::instance()->plugin_path . 'build/', 'Seating/' );
	}
}
