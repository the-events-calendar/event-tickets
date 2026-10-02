<?php
/**
 * Resolves a ticket's sale price rule against its event and writes the sale price dates it resolves to.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Tickets\Commerce\Ticket;

/**
 * Writes the dates of a ticket's stored sale price rule into the sale price dates the on-sale check reads.
 *
 * The ticket save and the event listener both write through it, so a saved ticket and a moved event resolve alike.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Sale_Price_Dates {
	/**
	 * The store of the ticket rules.
	 *
	 * @since TBD
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The sale price window resolver.
	 *
	 * @since TBD
	 *
	 * @var Sale_Price_Window
	 */
	private Sale_Price_Window $sale_price_window;

	/**
	 * Sale_Price_Dates constructor.
	 *
	 * @since TBD
	 *
	 * @param Rule_Store        $rule_store        The store of the ticket rules.
	 * @param Sale_Price_Window $sale_price_window The sale price window resolver.
	 */
	public function __construct( Rule_Store $rule_store, Sale_Price_Window $sale_price_window ) {
		$this->rule_store        = $rule_store;
		$this->sale_price_window = $sale_price_window;
	}

	/**
	 * Writes the dates the ticket's stored sale price rule resolves to.
	 *
	 * Nothing is written for a ticket without a valid sale price rule or without a sale price, or for a post without
	 * valid event dates. A specific boundary keeps the date the ticket has.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 * @param int $post_id   The event post ID.
	 *
	 * @return void
	 */
	public function write( int $ticket_id, int $post_id ): void {
		$rule = Sale_Price_Rule::from_stored( $this->rule_store->get( $ticket_id ) );

		$dates = $rule && $this->has_sale_price( $ticket_id ) ? $this->sale_price_window->resolve_for_event( $rule, $post_id ) : null;

		if ( ! $dates ) {
			return;
		}

		$meta_keys = [
			'start' => Ticket::$sale_price_start_date_key,
			'end'   => Ticket::$sale_price_end_date_key,
		];

		foreach ( $meta_keys as $key => $meta_key ) {
			if ( null !== $dates[ $key ] ) {
				update_post_meta( $ticket_id, $meta_key, $dates[ $key ] );
			}
		}
	}

	/**
	 * Returns whether a ticket has a sale price.
	 *
	 * The sale price save removes every sale price meta when the sale price is unchecked or not lower than the price.
	 *
	 * @since TBD
	 *
	 * @param int $ticket_id The ticket post ID.
	 *
	 * @return bool Whether the ticket's sale price is enabled.
	 */
	public function has_sale_price( int $ticket_id ): bool {
		return tribe_is_truthy( get_post_meta( $ticket_id, Ticket::$sale_price_checked_key, true ) );
	}
}
