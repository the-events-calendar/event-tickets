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
		remove_filter( Occurrence_Guard::FILTER, $this->container->callback( Occurrence_Guard::class, 'remember' ), 9 );
		remove_filter( Occurrence_Guard::FILTER, $this->container->callback( Occurrence_Guard::class, 'restore' ), PHP_INT_MAX );
		remove_filter( 'get_post_metadata', [ $this->container->get( Meta_Shim::class ), 'read' ], 9 );
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

		/*
		 * Hooked through container callbacks: they resolve the current instance when called, and a second
		 * registration, which binds the singleton again, still removes the hooks of the first.
		 */
		$this->container->singleton( Occurrence_Guard::class );
		// Before ECP's callback at 10, and after every other one.
		add_filter( Occurrence_Guard::FILTER, $this->container->callback( Occurrence_Guard::class, 'remember' ), 9 );
		add_filter( Occurrence_Guard::FILTER, $this->container->callback( Occurrence_Guard::class, 'restore' ), PHP_INT_MAX );

		wp_cache_add_non_persistent_groups( [ Rows::CACHE_GROUP ] );
		$this->container->singleton( Rows::class );
		$this->container->singleton( Meta_Shim::class );
		// Before ECP's meta cache hydration at 10, which reads any ID above its own base as a date and queries for it.
		add_filter( 'get_post_metadata', [ $this->container->get( Meta_Shim::class ), 'read' ], 9, 4 );
	}
}
