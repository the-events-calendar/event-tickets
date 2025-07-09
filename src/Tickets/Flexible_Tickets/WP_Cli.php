<?php
/**
 * Handles the feature implementation with WP CLI.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;

/**
 * Class WP_Cli.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class WP_Cli extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		\WP_CLI::add_hook( 'before_run_command', [ $this, 'maybe_disable_foreign_key_checks' ] );
		\WP_CLI::add_hook( 'after_invoke::site_empty', [ $this, 'truncate_custom_tables' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		// There is no API to remove hooks from WP CLI.
	}

	/**
	 * Make sure this controller will activate only if WP CLI is active.
	 *
	 * @since 5.8.0
	 *
	 * @return bool Whether the controller should be active or not.
	 */
	public function is_active(): bool {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Disables the foreign key checks before running the `wp site empty` command.
	 *
	 * Some custom tables managed by the plugin have foreign keys on the posts and users tables,
	 * those tables would not be truncated by the `wp site empty` command otherwise. Foreign key
	 * checks will be re-enabled after the command is run, in the `truncate_custom_tables` method.
	 *
	 * @since 5.8.0
	 * @since 5.18.1 Added default value and safeguards.
	 *
	 * @version 5.18.1
	 *
	 * @param array $args The arguments passed to the WP CLI command.
	 *
	 * @return bool Whether the foreign key checks were disabled or not.
	 */
	public function maybe_disable_foreign_key_checks( array $args = [] ): bool {
		if ( empty( $args[0] ) || 'site' !== $args[0] || empty( $args[1] ) || 'empty' !== $args[1] ) {
			return false;
		}

		global $wpdb;
		$wpdb->query( 'SET FOREIGN_KEY_CHECKS=0;' );

		return true;
	}

	/**
	 * Truncates the custom tables.
	 *
	 * @since 5.8.0
	 *
	 * @return int The number of truncated tables deleted.
	 */
	public function truncate_custom_tables(): int {
		return $this->container->make( Custom_Tables::class )->truncate_tables();
	}
}
