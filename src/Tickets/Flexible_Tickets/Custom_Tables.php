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
}