<?php
/**
 * Warns on an event's edit screen about the tickets whose relative sale dates end before they start.
 *
 * @since 5.31.2
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */

declare( strict_types=1 );

namespace TEC\Tickets\Relative_Sale_Dates;

use TEC\Common\StellarWP\AdminNotices\AdminNotices;

/**
 * Names the tickets of the event being edited whose sales, or sale price, window ends before it starts.
 *
 * Such a window is written when the event's dates are saved after the ticket's, as on a new event, or when the event
 * moves: the ticket save refuses it, but there is no ticket save then to refuse. The ticket is off sale, or off its
 * sale price, until its dates are fixed, and the notice shows until they are.
 *
 * @since 5.31.2
 *
 * @package TEC\Tickets\Relative_Sale_Dates
 */
final class Inverted_Window_Notice {
	/**
	 * The notice ID.
	 *
	 * @since 5.31.2
	 *
	 * @var string
	 */
	private const ID = 'tec-tickets-relative-sale-dates-inverted-window';

	/**
	 * The store of the ticket rules.
	 *
	 * @since 5.31.2
	 *
	 * @var Rule_Store
	 */
	private Rule_Store $rule_store;

	/**
	 * The reader of the dates of every kind of window.
	 *
	 * @since 5.31.2
	 *
	 * @var Ticket_Dates
	 */
	private Ticket_Dates $ticket_dates;

	/**
	 * Inverted_Window_Notice constructor.
	 *
	 * @since 5.31.2
	 *
	 * @param Rule_Store   $rule_store   The store of the ticket rules.
	 * @param Ticket_Dates $ticket_dates The reader of the dates of every kind of window.
	 */
	public function __construct( Rule_Store $rule_store, Ticket_Dates $ticket_dates ) {
		$this->rule_store   = $rule_store;
		$this->ticket_dates = $ticket_dates;
	}

	/**
	 * Shows the notice on the edit screen of an event with tickets whose window ends before it starts.
	 *
	 * @since 5.31.2
	 *
	 * @return void
	 */
	public function show_on_edit_screen(): void {
		$post_id = absint( tec_get_request_var( 'post', 0 ) );

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$lines = $this->get_lines( $post_id );

		if ( ! $lines ) {
			return;
		}

		AdminNotices::show(
			self::ID,
			static fn(): string => '<p>' . implode( '</p><p>', $lines ) . '</p>'
		)
			->asWarning()
			->notDismissible()
			->withoutAutoParagraph();
	}

	/**
	 * Gets a line of the notice for each window of the event's ruled tickets that ends before it starts.
	 *
	 * @since 5.31.2
	 *
	 * @param int $post_id The event post ID.
	 *
	 * @return string[] The lines, escaped.
	 */
	private function get_lines( int $post_id ): array {
		$lines = [];

		foreach ( $this->rule_store->get_ticket_ids_for_event( $post_id ) as $ticket_id ) {
			foreach ( Window_Kind::all() as $kind ) {
				if ( ! $this->ticket_dates->ends_before_it_starts( (int) $ticket_id, $kind ) ) {
					continue;
				}

				$name    = '<strong>' . esc_html( get_the_title( (int) $ticket_id ) ) . '</strong>';
				$lines[] = Window_Kind::SALE_PRICE === $kind->get_id()
					? sprintf(
						// Translators: %s is the ticket name.
						esc_html__( 'The sale price of %s ends before it starts, so it never applies. Edit the ticket\'s sale price dates.', 'event-tickets' ),
						$name
					)
					: sprintf(
						// Translators: %s is the ticket name.
						esc_html__( 'The sales of %s end before they start, so it is not on sale. Edit the ticket\'s sales dates.', 'event-tickets' ),
						$name
					);
			}
		}

		return $lines;
	}
}
