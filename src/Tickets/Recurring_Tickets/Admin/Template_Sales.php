<?php
/**
 * The sales of each recurring event ticket over every date of its event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Tickets\Recurring_Tickets\Hydrator;
use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;

/**
 * Adds up, per template, the sales and the availability of its rows: a template is never sold, its rows are.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Template_Sales {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The hydrator, for what a shared row has left in its date's pool.
	 *
	 * @since TBD
	 *
	 * @var Hydrator
	 */
	private Hydrator $hydrator;

	/**
	 * Template_Sales constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows     $rows The rows repository.
	 * @param Hydrator $hydrator The hydrator.
	 */
	public function __construct( Rows $rows, Hydrator $hydrator ) {
		$this->rows     = $rows;
		$this->hydrator = $hydrator;
	}

	/**
	 * Returns the sales and availability of each template of an event, over the dates it has rows on.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return array<int,array{sold: int, available: int}> By template ID; `available` is -1 when unlimited.
	 */
	public function for_event( int $event_id ): array {
		return $this->add_up( $this->rows->get_by_post( $event_id ) );
	}

	/**
	 * Returns what an event's recurring event tickets have left to sell over every date: a date's shared capacity
	 * pool counts once, whatever number of tickets share it.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return int What is left, -1 when a ticket is unlimited.
	 */
	public function available( int $event_id ): int {
		$left  = 0;
		$pools = [];

		foreach ( $this->rows->get_by_post( $event_id ) as $row ) {
			if ( 'publish' !== $row->status ) {
				continue;
			}

			if ( -1 === (int) $row->capacity || null === $row->stock ) {
				return -1;
			}

			$numbers = $this->hydrator->pool_numbers( $row );

			if ( ! $numbers ) {
				$left += max( 0, (int) $row->stock );
				continue;
			}

			// ponytail: a capped row's own limit is left out; the pool bounds the date either way.
			$pools[ (int) $row->occurrence_id ] = max( 0, (int) $this->rows->get_pool( (int) $row->occurrence_id )->stock );
		}

		return $left + array_sum( $pools );
	}

	/**
	 * Returns the number of recurring event tickets sold over every date of an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event.
	 *
	 * @return int The number sold.
	 */
	public function sold( int $event_id ): int {
		return (int) array_sum( array_column( $this->for_event( $event_id ), 'sold' ) );
	}

	/**
	 * Adds up the sales and availability of rows, by template.
	 *
	 * @since TBD
	 *
	 * @param Ticket[] $rows The rows.
	 *
	 * @return array<int,array{sold: int, available: int}> By template ID; `available` is -1 when unlimited.
	 */
	private function add_up( array $rows ): array {
		$sales = [];
		foreach ( $rows as $row ) {
			if ( 'publish' !== $row->status ) {
				continue;
			}

			$template_id = (int) $row->parent_id;

			$sales[ $template_id ] ??= [
				'sold'      => 0,
				'available' => 0,
			];

			$sales[ $template_id ]['sold'] += (int) $row->sales;

			if ( -1 === $sales[ $template_id ]['available'] ) {
				continue;
			}

			$numbers = $this->hydrator->pool_numbers( $row );
			$left    = $numbers ? $numbers[1] : (int) $row->stock;

			$sales[ $template_id ]['available'] = -1 === (int) $row->capacity || null === $row->stock
				? -1
				: $sales[ $template_id ]['available'] + max( 0, $left );
		}

		return $sales;
	}
}
