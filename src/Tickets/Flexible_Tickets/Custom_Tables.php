<?php
/**
 * The custom tables controller.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Recurring_Tickets;
 */

namespace TEC\Tickets\Flexible_Tickets;

use TEC\Common\Provider\Controller;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Config as Schema_Config;
use TEC\Common\StellarWP\Schema\Register as Schema_Register;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Capacities_Relationships;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Posts;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Users;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;
use TEC\Common\StellarWP\Models\Config as Model_Config;

/**
 * Class Custom_Tables.
 *
 * @since   TBD
 *
 * @package TEC\Tickets\Flexible_Tickets;
 */
class Custom_Tables extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function do_register(): void {
		Schema_Config::set_container( $this->container );
		Schema_Config::set_db( DB::class );
		Model_Config::reset();
		Model_Config::setHookPrefix('tec-tickets-flexible-tickets');

		add_action( 'tribe_plugins_loaded', [ $this, 'register_tables' ] );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_plugins_loaded', [ $this, 'register_tables' ] );
	}

	/**
	 * Registers the custom tables and makes them available in the container as singletons.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function register_tables(): void {
		$this->container->singleton( Capacities::class, Schema_Register::table( Capacities::class ) );
		$this->container->singleton( Ticket_Groups::class, Schema_Register::table( Ticket_Groups::class ) );
		$this->container->singleton( Posts_And_Posts::class, Schema_Register::table( Posts_And_Posts::class ) );
		$this->container->singleton( Posts_And_Users::class, Schema_Register::table( Posts_And_Users::class ) );
		$this->container->singleton( Capacities_Relationships::class, Schema_Register::table( Capacities_Relationships::class ) );
		$this->container->singleton( Posts_And_Ticket_Groups::class, Schema_Register::table( Posts_And_Ticket_Groups::class ) );
	}

	/**
	 * Drops the custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of tables dropped.
	 */
	public function drop_tables(): int {
		$dropped = 0;

		DB::query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach (
			[
				Capacities::table_name(),
				Ticket_Groups::table_name(),
				Posts_And_Posts::table_name(),
				Posts_And_Users::table_name(),
				Capacities_Relationships::table_name(),
			] as $table
		) {
			$dropped += DB::query( "DROP TABLE IF EXISTS $table" );
		}
		DB::query( 'SET FOREIGN_KEY_CHECKS = 1' );

		return $dropped;
	}

	/**
	 * Truncates the custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of tables truncated.
	 */
	public function truncate_tables(): int {
		$truncated = 0;

		DB::query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach (
			[
				Capacities::table_name(),
				Ticket_Groups::table_name(),
				Posts_And_Posts::table_name(),
				Posts_And_Users::table_name(),
				Capacities_Relationships::table_name(),
			] as $table
		) {
			$truncated += DB::query( "TRUNCATE TABLE $table" );
		}
		DB::query( 'SET FOREIGN_KEY_CHECKS = 1' );

		return $truncated;
	}
}