<?php
/**
 * The Recurring Event Tickets repository.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Repositories
 */

namespace TEC\Tickets\Recurring_Tickets\Repositories;

use InvalidArgumentException;
use TEC\Common\Abstracts\Custom_Table_Repository;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Tables\Tickets as Tickets_Table;

/**
 * Class Tickets.
 *
 * Batched reads and writes of the rows of a date, a post or a template. Rows are arrays keyed by column name.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Repositories
 */
final class Tickets extends Custom_Table_Repository {
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
		return Ticket::class;
	}

	/**
	 * Inserts rows in one query.
	 *
	 * The table cannot hold a falsy column default, so `sales` and `menu_order` are written as 0, and `created_at`
	 * as the current UTC time, when a row leaves them out.
	 *
	 * @since TBD
	 *
	 * @param array<int,array<string,mixed>> $rows The rows to insert. Every row must set the same columns, in any order.
	 *
	 * @return int The number of rows inserted.
	 *
	 * @throws InvalidArgumentException If a row sets different columns than the first row. A failed insert, for
	 *                                  example a second row for a template and date, throws a DatabaseQueryException.
	 */
	public function insert_many( array $rows ): int {
		if ( ! $rows ) {
			return 0;
		}

		$columns  = array_fill_keys( array_keys( reset( $rows ) ), null );
		$defaults = [
			'sales'      => 0,
			'menu_order' => 0,
			'created_at' => gmdate( 'Y-m-d H:i:s' ),
		];

		foreach ( $rows as $index => $row ) {
			if ( array_diff_key( $row, $columns ) || array_diff_key( $columns, $row ) ) {
				throw new InvalidArgumentException( "Row {$index} sets different columns than the first row." );
			}

			$rows[ $index ] = array_replace( $columns, $defaults, $row );
		}

		return (int) Tickets_Table::insert_many( array_values( $rows ) );
	}

	/**
	 * Returns the rows of a date.
	 *
	 * @since TBD
	 *
	 * @param int $occurrence_id The occurrence ID.
	 *
	 * @return Ticket[] The rows, ordered by menu order, then ID.
	 */
	public function get_by_occurrence( int $occurrence_id ): array {
		$rows = $this->get_by( 'occurrence_id', $occurrence_id );

		usort( $rows, static fn( Ticket $a, Ticket $b ) => [ $a->menu_order, $a->id ] <=> [ $b->menu_order, $b->id ] );

		return $rows;
	}

	/**
	 * Returns the rows of a post, every date of it.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The real event post ID.
	 *
	 * @return Ticket[] The rows, ordered by ID.
	 */
	public function get_by_post( int $post_id ): array {
		return $this->get_by( 'post_id', $post_id );
	}

	/**
	 * Returns the rows of a template, one per date.
	 *
	 * @since TBD
	 *
	 * @param int $template_id The template ticket post ID.
	 *
	 * @return Ticket[] The rows, ordered by ID.
	 */
	public function get_by_template( int $template_id ): array {
		return $this->get_by( 'parent_id', $template_id );
	}

	/**
	 * Deletes the rows of a date in one query.
	 *
	 * @since TBD
	 *
	 * @param int $occurrence_id The occurrence ID.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_by_occurrence( int $occurrence_id ): int {
		return $this->delete_by( 'occurrence_id', $occurrence_id );
	}

	/**
	 * Deletes the rows of a template in one query.
	 *
	 * @since TBD
	 *
	 * @param int $template_id The template ticket post ID.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_by_template( int $template_id ): int {
		return $this->delete_by( 'parent_id', $template_id );
	}

	/**
	 * Deletes the rows of a post in one query.
	 *
	 * @since TBD
	 *
	 * @param int $post_id The real event post ID.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_by_post( int $post_id ): int {
		return $this->delete_by( 'post_id', $post_id );
	}

	/**
	 * Returns the rows whose column holds a value, paging by ID so every page is stable.
	 *
	 * @since TBD
	 *
	 * @param string $column The column.
	 * @param int    $value  The value.
	 *
	 * @return Ticket[] The rows, ordered by ID.
	 */
	private function get_by( string $column, int $value ): array {
		$where = [
			[
				'column' => $column,
				'value'  => $value,
			],
		];
		$rows  = [];
		$page  = 1;

		do {
			$batch = Tickets_Table::paginate( $where, self::PAGE_SIZE, $page++ );
			$rows  = array_merge( $rows, $batch );
			$full  = count( $batch ) === self::PAGE_SIZE;
		} while ( $full );

		return $rows;
	}

	/**
	 * Deletes the rows whose column holds a value, in one query.
	 *
	 * @since TBD
	 *
	 * @param string $column The column.
	 * @param int    $value  The value.
	 *
	 * @return int The number of rows deleted.
	 */
	private function delete_by( string $column, int $value ): int {
		return $value > 0 ? (int) Tickets_Table::delete_many( [ $value ], $column ) : 0;
	}
}
