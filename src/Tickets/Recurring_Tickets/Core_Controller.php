<?php
/**
 * The Core tier of Recurring Event Tickets: what makes a row of the tickets table behave like a ticket.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;

/**
 * Class Core_Controller.
 *
 * Always active: tickets already sold must keep working, refunds and check-in included, when Events Calendar Pro
 * is off or the Recurrence tier is switched off. Nothing that needs the table registers when it cannot be created.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Core_Controller extends Controller_Contract {
	/**
	 * Unregisters the controller. The table and its rows are never dropped: they are tickets customers bought.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	public function unregister(): void {
		$guard = $this->container->get( Occurrence_Guard::class );
		remove_filter( Occurrence_Guard::FILTER, [ $guard, 'remember' ], 9 );
		remove_filter( Occurrence_Guard::FILTER, [ $guard, 'restore' ], PHP_INT_MAX );
		$shim = $this->container->get( Meta_Shim::class );
		remove_filter( 'get_post_metadata', [ $shim, 'read' ], 9 );
		foreach ( [ 'add', 'update', 'delete' ] as $write ) {
			remove_filter( "{$write}_post_metadata", [ $shim, 'refuse_write' ], PHP_INT_MIN );
		}
		remove_filter( 'tec_tickets_get_tickets', [ $this->container->get( Date_Tickets::class ), 'swap' ] );
	}

	/**
	 * Registers the table, then what needs it.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	protected function do_register(): void {
		try {
			Register::table( Tickets::class );
		} catch ( DatabaseQueryException $e ) {
			$this->error(
				'The Recurring Event Tickets table could not be registered.',
				[
					'error' => $e->getMessage(),
					'query' => $e->getQuery(),
				]
			);

			return;
		}

		$this->container->singleton( Occurrence_Guard::class );
		$guard = $this->container->get( Occurrence_Guard::class );
		// Before ECP's callback at 10, and after every other one.
		add_filter( Occurrence_Guard::FILTER, [ $guard, 'remember' ], 9 );
		add_filter( Occurrence_Guard::FILTER, [ $guard, 'restore' ], PHP_INT_MAX );

		wp_cache_add_non_persistent_groups( [ Rows::CACHE_GROUP ] );
		$this->container->singleton( Rows::class );
		$this->container->singleton( Meta_Shim::class );
		$shim = $this->container->get( Meta_Shim::class );

		/*
		 * ECP reads any ID above its own base as a date. Its meta cache hydration at 10 queries for one, and its
		 * update filter at 0 recurses forever on one that is not a date: the shim answers before both.
		 */
		add_filter( 'get_post_metadata', [ $shim, 'read' ], 9, 4 );
		foreach ( [ 'add', 'update', 'delete' ] as $write ) {
			add_filter( "{$write}_post_metadata", [ $shim, 'refuse_write' ], PHP_INT_MIN, 3 );
		}

		$this->container->singleton( Template_Guard::class );
		$this->container->singleton( Date_Tickets::class );
		add_filter( 'tec_tickets_get_tickets', [ $this->container->get( Date_Tickets::class ), 'swap' ], 10, 2 );
	}
}
