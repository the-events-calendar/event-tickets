<?php
/**
 * The Order Items repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */

namespace TEC\Tickets\Commerce\Order_Items\Repositories;

use InvalidArgumentException;
use TEC\Common\Abstracts\Custom_Table_Repository;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Tickets\Commerce\Order_Items\Models\Order_Item;
use TEC\Tickets\Commerce\Order_Items\Tables\Order_Items as Order_Items_Table;

/**
 * Class Order_Items.
 *
 * Batched reads and writes of an order's rows. Rows are arrays keyed by column name; every NOT NULL column
 * must be set on insert, since the table has no column defaults.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Commerce\Order_Items\Repositories
 */
class Order_Items extends Custom_Table_Repository {
	/**
	 * The most rows the table returns per query.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const PAGE_SIZE = 200;

	/**
	 * Returns the model class.
	 *
	 * @since TBD
	 *
	 * @return string The model class.
	 */
	public function get_model_class(): string {
		return Order_Item::class;
	}

	/**
	 * Inserts rows in one query.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to insert. Every row must set the same columns, in any order.
	 *
	 * @return int The number of rows inserted.
	 *
	 * @throws InvalidArgumentException If a row sets different columns than the first row. A failed insert, for
	 *                                  example a duplicate order line, throws a DatabaseQueryException.
	 */
	public function insert_many( array $rows ): int {
		if ( ! $rows ) {
			return 0;
		}

		// The table takes the column list from the first row and writes every row's values in its own key order.
		$columns = array_fill_keys( array_keys( reset( $rows ) ), null );

		foreach ( $rows as $index => $row ) {
			if ( array_diff_key( $row, $columns ) || array_diff_key( $columns, $row ) ) {
				throw new InvalidArgumentException( "Row {$index} sets different columns than the first row." );
			}

			$rows[ $index ] = array_replace( $columns, $row );
		}

		return Order_Items_Table::insert_many( array_values( $rows ) );
	}

	/**
	 * Returns an order's rows in insertion order.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return Order_Item[] The rows, ordered by ID ascending.
	 */
	public function get_by_order( int $order_id ): array {
		$where = [
			[
				'column' => 'order_id',
				'value'  => $order_id,
			],
		];
		$rows  = [];
		$page  = 1;

		// The table caps a page at 200 rows; an order rarely has more, so this is one query in practice.
		do {
			$batch = Order_Items_Table::paginate( $where, self::PAGE_SIZE, $page++ );
			$rows  = array_merge( $rows, $batch );
			$full  = count( $batch ) === self::PAGE_SIZE;
		} while ( $full );

		return $rows;
	}

	/**
	 * Updates rows in place, keeping their IDs.
	 *
	 * The table runs one query per row, inside a transaction of its own. Its `START TRANSACTION` implicitly commits
	 * any transaction the caller has open, so do not call this inside one.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to update, each with its `id` and the columns to change.
	 *
	 * @return bool Whether every row was updated.
	 *
	 * @throws DatabaseQueryException If an update fails.
	 */
	public function update_many( array $rows ): bool {
		return Order_Items_Table::update_many( $rows );
	}

	/**
	 * Deletes all the rows of an order in one query.
	 *
	 * @since TBD
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_by_order( int $order_id ): int {
		return $this->delete_where( [ $order_id ], 'order_id' );
	}

	/**
	 * Deletes the given rows in one query.
	 *
	 * @since TBD
	 *
	 * @param int[] $ids The row IDs.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_many( array $ids ): int {
		return $this->delete_where( $ids, Order_Items_Table::uid_column() );
	}

	/**
	 * Deletes the rows whose column holds one of the values.
	 *
	 * @since TBD
	 *
	 * @param int[]  $values The column values.
	 * @param string $column The column.
	 *
	 * @return int The number of rows deleted.
	 */
	private function delete_where( array $values, string $column ): int {
		// The table interpolates non-numeric values into the query unescaped.
		$values = array_filter( array_map( 'absint', $values ) );

		if ( ! $values ) {
			return 0;
		}

		return Order_Items_Table::delete_many( $values, $column );
	}
}
