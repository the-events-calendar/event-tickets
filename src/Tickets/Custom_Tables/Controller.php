<?php
/**
 * Custom tables controller.
 *
 * @since TBD
 */

declare( strict_types=1 );

namespace TEC\Tickets\Custom_Tables;

use InvalidArgumentException;
use TEC\Common\Contracts\Provider\Controller as CommonController;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Posts_And_Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups;

/**
 * Class Controller
 *
 * @since TBD
 */
class Controller extends CommonController {

	/**
	 * The callback to register the tables.
	 *
	 * @since TBD
	 *
	 * @var callable
	 */
	protected $register_callback;

	/**
	 * The tables that should be registered as singletons.
	 *
	 * @since TBD
	 *
	 * @var Table[]
	 */
	protected $singleton_tables = [
		Ticket_Groups::class,
		Posts_And_Ticket_Groups::class,
	];

	/**
	 * ServiceProvider constructor.
	 *
	 * @param Container $container
	 */
	public function __construct( Container $container ) {
		parent::__construct( $container );

		// Set up the callback function to register the tables.
		$this->register_callback = function () {
			$this->register_tables();
		};
	}

	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_plugins_loaded', $this->register_callback );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * Bound implementations should not be removed in this method!
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_plugins_loaded', $this->register_callback );
	}

	/**
	 * Registers the custom tables and makes them available in the container as singletons.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function register_tables() {
		foreach ( $this->singleton_tables as $table_class ) {
			$this->container->singleton( $table_class, Register::table( $table_class ) );
		}
	}

	/**
	 * Drops the custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of tables dropped.
	 */
	public function drop_tables(): int {
		return $this->db_query( 'drop' );
	}

	/**
	 * Truncates the custom tables.
	 *
	 * @since TBD
	 *
	 * @return int The number of tables truncated.
	 */
	public function truncate_tables() {
		return $this->db_query( 'truncate' );
	}

	/**
	 * Executes a query on the custom tables.
	 *
	 * @since TBD
	 *
	 * @param string $type The type of query to execute. Can be "drop" or "truncate".
	 *
	 * @return int The number of affected rows.
	 * @throws InvalidArgumentException If the query type is invalid.
	 */
	protected function db_query( string $type ): int {
		switch ( $type ) {
			case 'drop':
				$query = 'DROP TABLE IF EXISTS %s';
				break;
			case 'truncate':
				$query = 'TRUNCATE TABLE %s';
				break;
			default:
				throw new InvalidArgumentException( 'Invalid query type' );
		}

		$affected_rows = 0;

		DB::query( 'SET FOREIGN_KEY_CHECKS = 0' );

		foreach ( $this->singleton_tables as $table ) {
			$affected_rows += DB::query(
				DB::prepare( $query, $table::table_name() )
			);
		}

		DB::query( 'SET FOREIGN_KEY_CHECKS = 1' );

		return $affected_rows;
	}
}
