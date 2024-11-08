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
 * Class AssetGroupPath.
 *
 * @since TBD
 *
 * @package TEC\Controller;
 */
class AssetGroupPath extends Controller_Contract {

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_seating_tables_cron', [ Sessions::class, 'remove_expired_sessions' ] );
		wp_clear_scheduled_hook( 'tec_tickets_seating_tables_cron' );
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
