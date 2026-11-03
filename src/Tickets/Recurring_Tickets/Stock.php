<?php
/**
 * Changes the stock and sales of a Recurring Event Tickets row.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Stock.
 *
 * Every change is one statement, so its check and its write cannot be separated by another buyer: a sale never takes
 * stock below zero. A row is unlimited when its capacity is -1: it has no stock (NULL) and counts sales only. A limited
 * row without stock is inconsistent data, and refuses to sell.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Stock {
	/**
	 * The rows.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * Stock constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
	}

	/**
	 * Sells tickets of a row: sales up, stock down, only if there is enough stock.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many tickets.
	 *
	 * @return bool Whether they were sold. False means not enough stock, and nothing changed.
	 */
	public function sell( int $row_id, int $quantity ): bool {
		if ( $quantity < 1 ) {
			return false;
		}

		$pool = $this->pool_of( $row_id );

		// The pool first: its guard is what stops two shared tickets of one date from overselling it.
		if ( $pool && ! $this->write( $pool, 'UPDATE %i SET sales = sales + %d, stock = stock - %d WHERE id = %d AND stock >= %d', [ $quantity, $quantity, $pool, $quantity ] ) ) {
			return false;
		}

		$sold = $this->write(
			$row_id,
			'UPDATE %i SET sales = sales + %d, stock = IF( capacity = -1, NULL, stock - %d ) WHERE id = %d AND ( capacity = -1 OR stock >= %d )',
			[ $quantity, $quantity, $row_id, $quantity ]
		) > 0;

		if ( $pool && ! $sold ) {
			$this->write( $pool, 'UPDATE %i SET stock = stock + %d, sales = IF( sales >= %d, sales - %d, 0 ) WHERE id = %d', [ $quantity, $quantity, $quantity, $pool ] );
		}

		return $sold;
	}

	/**
	 * Returns tickets to a row: sales down, never below zero; stock what the capacity leaves, never below zero.
	 *
	 * MySQL sets columns left to right, so the stock reads the sales already lowered.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many tickets.
	 *
	 * @return void
	 */
	public function release( int $row_id, int $quantity ): void {
		if ( $quantity < 1 ) {
			return;
		}

		// A row gives back no more than it sold.
		$this->in_pool( $row_id, 'UPDATE %i SET sales = IF( sales >= %d, sales - %d, 0 ), stock = IF( capacity > sales, capacity - sales, 0 ) WHERE id = %d', min( $quantity, $this->sales( $row_id ) ) );

		$this->write(
			$row_id,
			'UPDATE %i SET sales = IF( sales >= %d, sales - %d, 0 ), stock = IF( capacity = -1, NULL, IF( capacity > sales, capacity - sales, 0 ) ) WHERE id = %d',
			[ $quantity, $quantity, $row_id ]
		);
	}

	/**
	 * Sells tickets of a row whatever its stock is left: sales up, stock what the capacity leaves, never below zero.
	 *
	 * For a sale that lost the race for the last tickets after its checkout check: its attendees exist, so its sales
	 * must too, or the leftover seats would sell again.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many tickets.
	 *
	 * @return void
	 */
	public function oversell( int $row_id, int $quantity ): void {
		if ( $quantity < 1 ) {
			return;
		}

		$this->write(
			$row_id,
			'UPDATE %i SET sales = sales + %d, stock = IF( capacity = -1, NULL, IF( capacity > sales, capacity - sales, 0 ) ) WHERE id = %d',
			[ $quantity, $row_id ]
		);
	}

	/**
	 * Raises a row's sales only, as `Commerce\Ticket::increase_ticket_sales_by()` does for a post.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many.
	 *
	 * @return int The row's sales afterwards.
	 */
	public function add_sales( int $row_id, int $quantity ): int {
		$this->in_pool( $row_id, 'UPDATE %i SET sales = sales + %d WHERE id = %d', max( 0, $quantity ) );
		$this->write( $row_id, 'UPDATE %i SET sales = sales + %d WHERE id = %d', [ max( 0, $quantity ), $row_id ] );

		return $this->sales( $row_id );
	}

	/**
	 * Lowers a row's sales only, never below zero, as `Commerce\Ticket::decrease_ticket_sales_by()` does for a post.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many.
	 *
	 * @return int The row's sales afterwards.
	 */
	public function remove_sales( int $row_id, int $quantity ): int {
		$quantity = max( 0, $quantity );
		$this->in_pool( $row_id, 'UPDATE %i SET sales = IF( sales >= %d, sales - %d, 0 ) WHERE id = %d', min( $quantity, $this->sales( $row_id ) ) );
		$this->write( $row_id, 'UPDATE %i SET sales = IF( sales >= %d, sales - %d, 0 ) WHERE id = %d', [ $quantity, $quantity, $row_id ] );

		return $this->sales( $row_id );
	}

	/**
	 * Raises a row's stock only, as `Commerce\Ticket::increase_ticket_stock_by()` does for a post. An unlimited row stays
	 * without stock.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many.
	 *
	 * @return bool Whether the row exists.
	 */
	public function add_stock( int $row_id, int $quantity ): bool {
		$this->in_pool( $row_id, 'UPDATE %i SET stock = LEAST( stock + %d, capacity ) WHERE id = %d', max( 0, $quantity ) );
		$this->write( $row_id, 'UPDATE %i SET stock = IF( capacity = -1, NULL, COALESCE( stock, 0 ) + %d ) WHERE id = %d', [ max( 0, $quantity ), $row_id ] );

		return null !== $this->rows->find( $row_id );
	}

	/**
	 * Lowers a row's stock only, never below zero, as `Commerce\Ticket::decrease_ticket_stock_by()` does for a post.
	 *
	 * @since TBD
	 *
	 * @param int $row_id   The row ID.
	 * @param int $quantity How many.
	 *
	 * @return bool Whether the row exists.
	 */
	public function remove_stock( int $row_id, int $quantity ): bool {
		$quantity = max( 0, $quantity );
		$this->in_pool( $row_id, 'UPDATE %i SET stock = IF( stock >= %d, stock - %d, 0 ) WHERE id = %d', $quantity );
		$this->write( $row_id, 'UPDATE %i SET stock = IF( capacity = -1, NULL, IF( stock >= %d, stock - %d, 0 ) ) WHERE id = %d', [ $quantity, $quantity, $row_id ] );

		return null !== $this->rows->find( $row_id );
	}

	/**
	 * Reads a row's stock and locks the row until the transaction ends, for the checkout stock check.
	 *
	 * @since TBD
	 *
	 * @param int $row_id The row ID.
	 *
	 * @return int|null The stock, or null when the row has none (unlimited) or is missing.
	 */
	public function lock( int $row_id ): ?int {
		$pool = $this->pool_of( $row_id );
		// The pool before the row, as every shared sale takes them: two carts never wait on each other in a circle.
		$left = $pool ? DB::get_var( DB::prepare( 'SELECT stock FROM %i WHERE id = %d FOR UPDATE', Tickets::table_name(), $pool ) ) : null;

		$stock = DB::get_var( DB::prepare( 'SELECT stock FROM %i WHERE id = %d FOR UPDATE', Tickets::table_name(), $row_id ) );

		if ( null === $stock ) {
			return null;
		}

		return null === $left ? (int) $stock : min( (int) $stock, (int) $left );
	}

	/**
	 * Returns the ID of the pool a row sells from: its date's, for a shared or capped row.
	 *
	 * @since TBD
	 *
	 * @param int $row_id The row ID.
	 *
	 * @return int|null The pool's row ID, or null for a row that sells from its own capacity.
	 */
	private function pool_of( int $row_id ): ?int {
		$row = $this->rows->find( $row_id );

		if ( ! $row || -1 === (int) $row->capacity || ! in_array( $row->stock_mode, [ Global_Stock::GLOBAL_STOCK_MODE, Global_Stock::CAPPED_STOCK_MODE ], true ) ) {
			return null;
		}

		$pool = $this->rows->get_pool( (int) $row->occurrence_id );

		return $pool ? (int) $pool->id : null;
	}

	/**
	 * Runs one write against the pool a row sells from, if it sells from one.
	 *
	 * @since TBD
	 *
	 * @param int    $row_id   The row ID.
	 * @param string $query    The query, with `%i` for the table, the quantity once per `%d` but the last, and the
	 *                         pool's ID last.
	 * @param int    $quantity How many.
	 *
	 * @return void
	 */
	private function in_pool( int $row_id, string $query, int $quantity ): void {
		$pool = $this->pool_of( $row_id );

		if ( $pool && $quantity > 0 ) {
			$this->write( $pool, $query, array_merge( array_fill( 0, substr_count( $query, '%d' ) - 1, $quantity ), [ $pool ] ) );
		}
	}

	/**
	 * Runs one write against a row and forgets what was read of it.
	 *
	 * @since TBD
	 *
	 * @param int            $row_id The row ID.
	 * @param string         $query  The query, with `%i` for the table first.
	 * @param array<int,int> $args   The other placeholders' values.
	 *
	 * @return int The number of rows changed.
	 */
	private function write( int $row_id, string $query, array $args ): int {
		$changed = (int) DB::query( DB::prepare( $query, Tickets::table_name(), ...$args ) );
		$this->rows->forget( $row_id );

		return $changed;
	}

	/**
	 * Returns a row's sales as stored.
	 *
	 * @since TBD
	 *
	 * @param int $row_id The row ID.
	 *
	 * @return int The sales, 0 for a missing row.
	 */
	private function sales( int $row_id ): int {
		$row = $this->rows->find( $row_id );

		return $row ? (int) $row->sales : 0;
	}
}
