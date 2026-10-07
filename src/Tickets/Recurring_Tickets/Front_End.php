<?php
/**
 * Checks the rows a buyer sends to the cart.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */

namespace TEC\Tickets\Recurring_Tickets;

use TEC\Tickets\Recurring_Tickets\Models\Ticket;
use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Keeps a row out of the cart unless it can be sold now, on the date the buyer sent it from.
 *
 * The cart already refuses a ticket that cannot be read, which a draft row is to a visitor. Without the Recurrence
 * tier, ECP is off and no row is sold: each would sell a date that no page shows.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets
 */
final class Front_End {
	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The row hydrator.
	 *
	 * @since TBD
	 *
	 * @var Hydrator
	 */
	private Hydrator $hydrator;

	/**
	 * Front_End constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows     $rows     The rows repository.
	 * @param Hydrator $hydrator The row hydrator.
	 */
	public function __construct( Rows $rows, Hydrator $hydrator ) {
		$this->rows     = $rows;
		$this->hydrator = $hydrator;
	}

	/**
	 * Drops from the cart's data the rows that cannot be sold from the page they were sent from.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $data The cart's data: the page's post ID and the tickets.
	 *
	 * @return array<string,mixed> The data, without those rows.
	 */
	public function filter_cart_data( $data ): array {
		$data = (array) $data;

		if ( empty( $data['tickets'] ) || ! is_array( $data['tickets'] ) ) {
			return $data;
		}

		$post_id = (int) ( $data['post_id'] ?? 0 );

		$data['tickets'] = array_values(
			array_filter(
				$data['tickets'],
				fn( $ticket ) => ! Ticket_ID::is_table_ticket( (int) ( $ticket['ticket_id'] ?? 0 ) ) || $this->can_sell( $ticket, $post_id )
			)
		);

		return $data;
	}

	/**
	 * Whether a row can be sold now from a page.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed> $ticket  The cart's ticket entry.
	 * @param int                 $post_id The ID of the page it was sent from, 0 if none.
	 *
	 * @return bool Whether the row can be sold.
	 */
	private function can_sell( array $ticket, int $post_id ): bool {
		if ( ! Recurrence_Controller::is_registered() ) {
			return false;
		}

		$row = $this->rows->find( Ticket_ID::to_row_id( (int) $ticket['ticket_id'] ) );

		if ( ! $row instanceof Ticket || 'publish' !== $row->status ) {
			return false;
		}

		$object = $ticket['obj'] ?? null;

		if ( ! $object instanceof Ticket_Object || ! $object->date_in_range() ) {
			return false;
		}

		// Sent from a page: the row's date's, or its event's.
		return ! $post_id || in_array( $post_id, [ $this->hydrator->event_id( $row ), (int) $row->post_id ], true );
	}
}
