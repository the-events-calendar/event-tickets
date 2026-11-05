<?php
/**
 * The select that narrows an event's Attendees or Orders page to one date.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

/**
 * Renders the select of a recurring event's dates and reads the date chosen in it.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Date_Filter {
	/**
	 * The request variable of the chosen date.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const NAME = 'tec_tickets_recurring_date';

	/**
	 * Returns the date the request chose, 0 if none or one not offered.
	 *
	 * @since TBD
	 *
	 * @param array<int,string> $dates The dates offered: the start by the date's post ID.
	 *
	 * @return int The date's post ID.
	 */
	public function chosen( array $dates ): int {
		$date_id = absint( tribe_get_request_var( self::NAME ) );

		return isset( $dates[ $date_id ] ) ? $date_id : 0;
	}

	/**
	 * Returns the select of the dates, with the chosen one selected, and its button.
	 *
	 * @since TBD
	 *
	 * @param array<int,string> $dates The dates offered: the start by the date's post ID.
	 *
	 * @return string The select's HTML.
	 */
	public function render( array $dates ): string {
		$chosen  = $this->chosen( $dates );
		$options = sprintf( '<option value="">%s</option>', esc_html__( 'All dates', 'event-tickets' ) );
		foreach ( $dates as $date_id => $start ) {
			$options .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $date_id, selected( $chosen, $date_id, false ), esc_html( tribe_format_date( $start, true ) ) );
		}

		return sprintf(
			'<label class="screen-reader-text" for="%1$s">%2$s</label><select name="%1$s" id="%1$s">%3$s</select><input type="submit" class="button action" value="%4$s">',
			esc_attr( self::NAME ),
			esc_html__( 'Filter by date', 'event-tickets' ),
			$options,
			esc_attr__( 'Filter', 'event-tickets' )
		);
	}
}
