<?php
/**
 * Validates that the tickets in a cart are on sale.
 *
 * @since 5.31.2
 *
 * @package TEC\Tickets\Commerce
 */

namespace TEC\Tickets\Commerce;

use Tribe__Date_Utils as Dates;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Error;

/**
 * Class Sale_Window_Validator.
 *
 * The cart checks a ticket's sale window when the ticket is added, but the cart outlives that window,
 * so order creation checks it again.
 *
 * @since 5.31.2
 *
 * @package TEC\Tickets\Commerce
 */
final class Sale_Window_Validator {

	/**
	 * Validates that every ticket in a list of cart or order items is on sale now.
	 *
	 * @since 5.31.2
	 *
	 * @param array<int|string,array{ticket_id?:int|string}> $items The cart or order items.
	 *
	 * @return true|WP_Error True when every ticket is on sale, otherwise the error for the first ticket that is not.
	 */
	public function validate_items( array $items ) {
		foreach ( $items as $item ) {
			if ( empty( $item['ticket_id'] ) ) {
				continue;
			}

			$ticket = tribe( Ticket::class )->get_ticket( absint( $item['ticket_id'] ) );

			if ( ! $ticket instanceof Ticket_Object || $ticket->date_in_range() ) {
				continue;
			}

			return new WP_Error(
				'tec-tc-ticket-unavailable',
				$this->get_unavailable_message( $ticket ),
				[
					'status'    => 400,
					'ticket_id' => absint( $ticket->ID ),
				]
			);
		}

		return true;
	}

	/**
	 * Gets the message shown to a buyer whose ticket is outside its sale window.
	 *
	 * @since 5.31.2
	 *
	 * @param Ticket_Object $ticket The ticket outside its sale window.
	 *
	 * @return string The message.
	 */
	public function get_unavailable_message( Ticket_Object $ticket ): string {
		if ( $ticket->date_is_earlier() ) {
			return sprintf(
				/* translators: 1: plural ticket label, 2: sale start date, 3: sale start time */
				__( '%1$s will be available on %2$s at %3$s', 'event-tickets' ),
				tribe_get_ticket_label_plural( 'unavailable_future_display_date' ),
				$ticket->start_date,
				Dates::reformat( $ticket->start_time, tribe_get_time_format() )
			);
		}

		if ( $ticket->date_is_later() ) {
			return sprintf(
				/* translators: %s: plural ticket label */
				__( '%s are no longer available.', 'event-tickets' ),
				tribe_get_ticket_label_plural( 'unavailable_past' )
			);
		}

		// A ticket with no sale end date is also off sale once its event has ended.
		return sprintf(
			/* translators: %s: plural ticket label */
			__( 'There are no %s available at this time.', 'event-tickets' ),
			tribe_get_ticket_label_plural( 'unavailable_mixed' )
		);
	}
}
