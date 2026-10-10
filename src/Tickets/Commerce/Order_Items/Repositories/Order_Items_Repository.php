<?php
/**
 * The contract of the Order Items repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */

namespace TEC\Tickets\Commerce\Order_Items\Repositories;

use InvalidArgumentException;
use TEC\Tickets\Commerce\Order_Items\Models\Order_Item;

/**
 * Interface Order_Items_Repository.
 *
 * What the Order Items repository does, so a decorator, like the cached one, can stand in for it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */
interface Order_Items_Repository {
	/**
	 * Inserts rows in one query.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to insert. Every row must set the same columns, in any order.
	 *
	 * @return int The number of rows inserted.
	 *
	 * @throws InvalidArgumentException If a row sets different columns than the first row.
	 */
	public function insert_many( array $rows ): int;

	/**
	 * Returns an order's rows in list order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return Order_Item[] The rows, ordered by position, then ID, ascending.
	 */
	public function get_by_order( int $order_id ): array;

	/**
	 * Updates rows in place, keeping their IDs.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to update, each with its positive integer `id` and the columns to change.
	 *
	 * @return int The number of rows changed.
	 *
	 * @throws InvalidArgumentException If a row has no positive integer `id`.
	 */
	public function update_rows( array $rows ): int;

	/**
	 * Deletes all the rows of an order in one query.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_by_order( int $order_id ): int;

	/**
	 * Deletes the given rows in one query.
	 *
	 * @since TBD
	 *
	 * @param int[] $ids The row IDs.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_many( array $ids ): int;
}
