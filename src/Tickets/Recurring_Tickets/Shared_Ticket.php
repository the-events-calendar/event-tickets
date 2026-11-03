<?php
/**
 * A recurring event ticket that sells from its date's shared capacity pool.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Answers its capacity and stock from its row and its date's pool.
 *
 * The base class reads a shared ticket's capacity, stock and inventory from its event, and caches them per ticket.
 * A date's event is its Series for ECP, shared by every date, and a sale of a sibling ticket on the pool would leave
 * the cache stale.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Shared_Ticket extends Ticket_Object {
	/**
	 * The capacity: the pool's for a shared ticket, the cap for a capped one.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $shared_capacity;

	/**
	 * What is left to sell: the lower of the row's stock and the pool's.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private int $shared_stock;

	/**
	 * Shared_Ticket constructor.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data     The ticket's properties.
	 * @param int                 $capacity The capacity: the pool's for a shared ticket, the cap for a capped one.
	 * @param int                 $stock    What is left to sell: the lower of the row's stock and the pool's.
	 */
	public function __construct( array $data, int $capacity, int $stock ) {
		parent::__construct( $data );

		$this->shared_capacity = $capacity;
		$this->shared_stock    = max( 0, $stock );
	}

	/**
	 * Returns the capacity.
	 *
	 * @since TBD
	 *
	 * @return int The capacity.
	 */
	public function capacity() {
		return $this->shared_capacity;
	}

	/**
	 * Returns what is left to sell.
	 *
	 * @since TBD
	 *
	 * @param int|null $value Unused: a shared ticket's stock is its row's and its pool's.
	 *
	 * @return int What is left to sell.
	 */
	public function stock( $value = null ) {
		return $this->shared_stock;
	}

	/**
	 * Returns what is left to sell.
	 *
	 * @since TBD
	 *
	 * @return int What is left to sell.
	 */
	public function inventory() {
		return $this->shared_stock;
	}

	/**
	 * Returns what is left to sell.
	 *
	 * @since TBD
	 *
	 * @return int What is left to sell.
	 */
	public function available() {
		return $this->shared_stock;
	}
}
