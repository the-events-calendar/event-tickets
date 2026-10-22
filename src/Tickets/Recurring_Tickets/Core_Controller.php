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
	 * The container variable that is true once the table is registered.
	 *
	 * Code that reads the table in SQL of its own, rather than through the repository, checks it first.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const TABLE_READY = 'tec_tickets_recurring_tickets_table_ready';

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
		remove_filter( 'get_post_metadata', $this->container->callback( Meta_Shim::class, 'read' ), 9 );
		foreach ( [ 'add', 'update', 'delete' ] as $write ) {
			remove_filter( "{$write}_post_metadata", $this->container->callback( Meta_Shim::class, 'refuse_write' ), PHP_INT_MIN );
		}
		remove_filter( 'tec_tickets_get_tickets', $this->container->callback( Date_Tickets::class, 'swap' ) );
		remove_filter( 'tec_tickets_get_tickets', $this->container->callback( Template_Guard::class, 'drop_from_front_end' ), 20 );
		remove_filter( 'tec_events_pro_custom_tables_v1_is_provisional_post_id', [ Ticket_ID::class, 'filter_provisional_post_id' ] );
		remove_filter( 'tec_tickets_commerce_get_ticket_before', $this->container->callback( Commerce\Row_Post::class, 'filter_ticket' ) );
		remove_filter( 'tec_tickets_commerce_cart_prepare_data', $this->container->callback( Front_End::class, 'filter_cart_data' ) );
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

		$this->container->setVar( self::TABLE_READY, true );

		/*
		 * Hooked through container callbacks: they resolve the current instance when called, and a second
		 * registration, which binds the singleton again, still removes the hooks of the first.
		 */
		$this->container->singleton( Occurrence_Guard::class );
		// Before ECP's callback at 10, and after every other one.
		add_filter( Occurrence_Guard::FILTER, $this->container->callback( Occurrence_Guard::class, 'remember' ), 9 );
		add_filter( Occurrence_Guard::FILTER, $this->container->callback( Occurrence_Guard::class, 'restore' ), PHP_INT_MAX );

		$this->container->singleton( Rows::class );
		$this->container->singleton( Meta_Shim::class );

		/*
		 * ECP reads any ID above its own base as a date. Its meta cache hydration at 10 queries for one, and its
		 * update filter at 0 recurses forever on one that is not a date: the shim answers before both.
		 */
		add_filter( 'get_post_metadata', $this->container->callback( Meta_Shim::class, 'read' ), 9, 4 );
		foreach ( [ 'add', 'update', 'delete' ] as $write ) {
			add_filter( "{$write}_post_metadata", $this->container->callback( Meta_Shim::class, 'refuse_write' ), PHP_INT_MIN, 3 );
		}

		$this->container->singleton( Template_Guard::class );
		$this->container->singleton( Date_Tickets::class );
		$this->container->singleton( Commerce\Row_Post::class );
		$this->container->singleton( Front_End::class );
		add_filter( 'tec_tickets_get_tickets', $this->container->callback( Date_Tickets::class, 'swap' ), 10, 2 );
		// After the swap: a date's templates are already gone, any left are the event's own.
		add_filter( 'tec_tickets_get_tickets', $this->container->callback( Template_Guard::class, 'drop_from_front_end' ), 20, 2 );
		add_filter( 'tec_events_pro_custom_tables_v1_is_provisional_post_id', [ Ticket_ID::class, 'filter_provisional_post_id' ], 10, 2 );
		add_filter( 'tec_tickets_commerce_get_ticket_before', $this->container->callback( Commerce\Row_Post::class, 'filter_ticket' ), 10, 4 );
		add_filter( 'tec_tickets_commerce_cart_prepare_data', $this->container->callback( Front_End::class, 'filter_cart_data' ) );
	}
}
