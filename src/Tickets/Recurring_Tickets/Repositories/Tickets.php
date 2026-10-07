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
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Tickets\Ticket_Cache_Controller;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Ticket_ID;
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
	 * How many dates one priming query reads.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const PRIME_CHUNK = 500;


	/**
	 * The columns a single date may change: EngDoc section 2, per-date overrides.
	 *
	 * @since TBD
	 *
	 * @var string[]
	 */
	private const OVERRIDABLE = [ 'name', 'description', 'price', 'capacity', 'start_date', 'end_date', 'start_date_utc', 'end_date_utc', 'status' ];

	/**
	 * The rows read in this request, by ID; null for an ID without a row.
	 *
	 * Held here, not in the object cache: a row is never served from another request, and clearing it never
	 * depends on what the object cache supports. The repository is a singleton.
	 *
	 * @since TBD
	 *
	 * @var array<int,Ticket|null>
	 */
	private array $found = [];

	/**
	 * The IDs of each date's rows read in this request, by occurrence ID; the rows themselves are in `$found`.
	 *
	 * @since TBD
	 *
	 * @var array<int,int[]>
	 */
	private array $occurrence_rows = [];

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

		$inserted = (int) Tickets_Table::insert_many( array_values( $rows ) );
		// An ID read as missing may name one of these rows now, and a date may have more rows.
		$this->forget_all();

		return $inserted;
	}

	/**
	 * Returns a row by ID, read once per request.
	 *
	 * @since TBD
	 *
	 * @param int $row_id The row ID.
	 *
	 * @return Ticket|null The row, or null when there is none, or no table.
	 */
	public function find( int $row_id ): ?Ticket {
		if ( array_key_exists( $row_id, $this->found ) ) {
			return $this->found[ $row_id ];
		}

		try {
			$row = Tickets_Table::get_by_id( $row_id );
		} catch ( DatabaseQueryException $e ) {
			// No table, no rows.
			$row = null;
		}

		// A missing row is remembered too: deleted rows are read on every page that lists their attendees.
		$this->found[ $row_id ] = $row instanceof Ticket ? $row : null;

		return $this->found[ $row_id ];
	}

	/**
	 * Remembers a row just read, so reading it again by ID runs no query.
	 *
	 * @since TBD
	 *
	 * @param Ticket $row The row.
	 *
	 * @return void
	 */
	public function prime( Ticket $row ): void {
		$this->found[ (int) $row->id ] = $row;
	}

	/**
	 * Forgets a row after it was written: the row and every cache of its ticket.
	 *
	 * @since TBD
	 *
	 * @param int $row_id The row ID.
	 *
	 * @return void
	 */
	public function forget( int $row_id ): void {
		unset( $this->found[ $row_id ] );
		tribe( Ticket_Cache_Controller::class )->clean_ticket_cache( Ticket_ID::from_row_id( $row_id ) );
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
		// One query per date and request: the rows come back through find(), which a write makes read again.
		if ( ! isset( $this->occurrence_rows[ $occurrence_id ] ) ) {
			$read = $this->get_by( 'occurrence_id', $occurrence_id );
			array_map( [ $this, 'prime' ], $read );
			$this->occurrence_rows[ $occurrence_id ] = array_map( static fn( Ticket $row ) => (int) $row->id, $read );
		}

		$rows = array_values( array_filter( array_map( [ $this, 'find' ], $this->occurrence_rows[ $occurrence_id ] ) ) );

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
	 * Changes columns of one row only, and records their names in the row's overrides so the sync never overwrites
	 * them. The only writer of `overrides`.
	 *
	 * A capacity override also sets the stock to what is left to sell, none when unlimited.
	 *
	 * @since TBD
	 *
	 * @param int                 $row_id The row ID.
	 * @param array<string,mixed> $values The new values, keyed by column. Only name, description, price, capacity,
	 *                                    the sale dates and status can be overridden.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException If no value is given, a column cannot be overridden or the row does not exist;
	 *                                  nothing is written then.
	 */
	public function override( int $row_id, array $values ): void {
		$refused = array_diff( array_keys( $values ), self::OVERRIDABLE );

		if ( ! $values || $refused ) {
			throw new InvalidArgumentException( 'These columns cannot be overridden: ' . implode( ', ', $refused ?: [ '(none given)' ] ) . '.' );
		}

		$row = $this->find( $row_id );

		if ( ! $row instanceof Ticket ) {
			throw new InvalidArgumentException( "Row {$row_id} does not exist." );
		}

		// ponytail: read then write, not atomic; wrap in a transaction when the per-date override UI calls it.
		$overrides = array_values( array_unique( array_merge( (array) $row->overrides, array_keys( $values ) ) ) );
		$update    = array_merge( $values, [ 'overrides' => wp_json_encode( $overrides ) ] );

		if ( array_key_exists( 'capacity', $values ) ) {
			$capacity        = (int) $values['capacity'];
			$update['stock'] = -1 === $capacity ? null : max( 0, $capacity - (int) $row->sales );
		}

		DB::update( Tickets_Table::table_name(), $update, [ 'id' => $row_id ] );
		$this->forget( $row_id );
	}

	/**
	 * Reads the rows of many dates in one query, so that `get_by_occurrence()` finds them in memory.
	 *
	 * @since TBD
	 *
	 * @param int[] $occurrence_ids The occurrence IDs; those already read in this request are skipped.
	 *
	 * @return void
	 */
	public function prime_occurrences( array $occurrence_ids ): void {
		$occurrence_ids = array_values( array_diff( array_unique( array_map( 'intval', $occurrence_ids ) ), array_keys( $this->occurrence_rows ), [ 0 ] ) );

		foreach ( array_chunk( $occurrence_ids, self::PRIME_CHUNK ) as $chunk ) {
			foreach ( $chunk as $occurrence_id ) {
				$this->occurrence_rows[ $occurrence_id ] = [];
			}

			$where = [
				[
					'column'   => 'occurrence_id',
					'value'    => $chunk,
					'operator' => 'IN',
				],
			];
			$page  = 1;

			do {
				$batch = Tickets_Table::paginate( $where, self::PAGE_SIZE, $page++ );

				foreach ( $batch as $row ) {
					$this->prime( $row );
					$this->occurrence_rows[ (int) $row->occurrence_id ][] = (int) $row->id;
				}

				$full = count( $batch ) === self::PAGE_SIZE;
			} while ( $full );
		}
	}

	/**
	 * Points an event's rows of one date to another date.
	 *
	 * @since TBD
	 *
	 * @param int    $post_id   The event's post ID.
	 * @param int    $from      The occurrence ID the rows point to.
	 * @param int    $to        The occurrence ID they point to next.
	 * @param string $start     The new date's local start.
	 * @param string $start_utc The new date's UTC start.
	 *
	 * @return void
	 */
	public function repoint( int $post_id, int $from, int $to, string $start, string $start_utc ): void {
		$rows = $this->get_by( 'occurrence_id', $from );

		DB::update(
			Tickets_Table::table_name(),
			[
				'occurrence_id'        => $to,
				'occurrence_start'     => $start,
				'occurrence_start_utc' => $start_utc,
			],
			[
				'post_id'       => $post_id,
				'occurrence_id' => $from,
			]
		);

		foreach ( $rows as $row ) {
			$this->forget( (int) $row->id );
		}

		$this->occurrence_rows = [];
	}

	/**
	 * Moves rows to another event and template.
	 *
	 * @since TBD
	 *
	 * @param int[] $row_ids     The row IDs.
	 * @param int   $post_id     The event they belong to next.
	 * @param int   $template_id The template they belong to next.
	 *
	 * @return void
	 */
	public function move( array $row_ids, int $post_id, int $template_id ): void {
		$row_ids = array_values( array_filter( array_map( 'intval', $row_ids ) ) );

		if ( ! $row_ids ) {
			return;
		}

		$placeholders = implode( ', ', array_fill( 0, count( $row_ids ), '%d' ) );
		DB::query(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- One placeholder per ID.
			DB::prepare( "UPDATE %i SET post_id = %d, parent_id = %d WHERE id IN ({$placeholders})", Tickets_Table::table_name(), $post_id, $template_id, ...$row_ids )
		);

		array_map( [ $this, 'forget' ], $row_ids );
	}

	/**
	 * Deletes rows by ID.
	 *
	 * @since TBD
	 *
	 * @param int[] $row_ids The row IDs.
	 *
	 * @return int The number of rows deleted.
	 */
	public function delete_ids( array $row_ids ): int {
		$row_ids = array_values( array_filter( array_map( 'intval', $row_ids ) ) );

		if ( ! $row_ids ) {
			return 0;
		}

		$deleted = (int) Tickets_Table::delete_many( $row_ids, 'id' );
		array_map( [ $this, 'forget' ], $row_ids );
		$this->occurrence_rows = [];

		return $deleted;
	}

	/**
	 * Writes a template's values on its rows, except on the columns a row overrides.
	 *
	 * Rows without overrides take the values in one statement. A row keeps its sales: a new capacity sets its stock
	 * to the capacity less the sales, never below 0, or to NULL when the capacity is unlimited.
	 *
	 * @since TBD
	 *
	 * @param int                 $template_id The template ID.
	 * @param array<string,mixed> $values      The values, keyed by column; integers, strings or NULL.
	 *
	 * @return void
	 */
	public function refresh_template( int $template_id, array $values ): void {
		$rows = $this->get_by_template( $template_id );

		if ( ! $rows || ! $values ) {
			return;
		}

		[ $set, $args ] = $this->set_clause( $values );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The SET clause holds placeholders only.
		DB::query( DB::prepare( "UPDATE %i SET {$set} WHERE parent_id = %d AND overrides IS NULL", Tickets_Table::table_name(), ...array_merge( $args, [ $template_id ] ) ) );

		foreach ( $rows as $row ) {
			$own = array_diff_key( $values, array_flip( (array) $row->overrides ) );

			if ( $row->overrides && $own ) {
				[ $set, $args ] = $this->set_clause( $own );
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- The SET clause holds placeholders only.
				DB::query( DB::prepare( "UPDATE %i SET {$set} WHERE id = %d", Tickets_Table::table_name(), ...array_merge( $args, [ (int) $row->id ] ) ) );
			}

			$this->forget( (int) $row->id );
		}

		$this->occurrence_rows = [];
	}

	/**
	 * Builds the SET clause of an update, with the stock that follows a capacity.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $values The values, keyed by column.
	 *
	 * @return array{0: string, 1: array<int,mixed>} The clause, with placeholders, and its arguments.
	 */
	private function set_clause( array $values ): array {
		$set  = [];
		$args = [];

		foreach ( $values as $column => $value ) {
			$args[] = $column;

			if ( null === $value ) {
				$set[] = '%i = NULL';
				continue;
			}

			$set[]  = is_int( $value ) ? '%i = %d' : '%i = %s';
			$args[] = $value;
		}

		if ( array_key_exists( 'capacity', $values ) ) {
			$capacity = (int) $values['capacity'];

			if ( -1 === $capacity ) {
				$set[] = 'stock = NULL';
			} else {
				$set[]  = 'stock = GREATEST( %d - CAST( sales AS SIGNED ), 0 )';
				$args[] = $capacity;
			}
		}

		return [ implode( ', ', $set ), $args ];
	}

	/**
	 * Forgets every row and date read in this request.
	 *
	 * @since TBD
	 *
	 * @return void
	 */
	private function forget_all(): void {
		$this->found           = [];
		$this->occurrence_rows = [];
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
		if ( $value < 1 ) {
			return 0;
		}

		$deleted = (int) Tickets_Table::delete_many( [ $value ], $column );
		// Which rows went is not known without another query: forget every row read in this request.
		$this->forget_all();

		return $deleted;
	}
}
