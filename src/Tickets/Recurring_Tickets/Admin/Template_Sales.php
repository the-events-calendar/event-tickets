<?php
/**
 * The sales of each recurring event ticket over every date of its event.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

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
	 * Template_Sales constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows $rows The rows repository.
	 */
	public function __construct( Rows $rows ) {
		$this->rows = $rows;
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
		$sales = [];
		foreach ( $this->rows->get_by_post( $event_id ) as $row ) {
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

			$sales[ $template_id ]['available'] = -1 === (int) $row->capacity || null === $row->stock
				? -1
				: $sales[ $template_id ]['available'] + max( 0, (int) $row->stock );
		}

		return $sales;
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
}
