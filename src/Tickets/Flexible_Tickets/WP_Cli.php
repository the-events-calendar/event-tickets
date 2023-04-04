<?php
/**
 * Handles the feature implementation with WP CLI.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Provider\Controller;

/**
 * Class WP_Cli.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class WP_Cli extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		\WP_CLI::add_hook( 'after_invoke::site_empty', [ $this, 'truncate_custom_tables' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		// There is no API to remove hooks from WP CLI.
	}

	/**
	 * Make sure this controller will activate only if WP CLI is active.
	 *
	 * @since TBD
	 *
	 * @return bool Whether the controller should be active or not.
	 */
	public function is_active(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Truncates the custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of truncated tables deleted.
	 */
	public function truncate_custom_tables(): int {
		return $this->container->make( Custom_Tables::class )->truncate_tables();
	}
}