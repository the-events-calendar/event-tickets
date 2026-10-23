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
 * Renders the select of a recurring event's dates and reads the dates chosen in it.
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
	 * The value that chooses every date still to come.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	public const UPCOMING = 'upcoming';

	/**
	 * The event's dates.
	 *
	 * @since TBD
	 *
	 * @var Event_Dates
	 */
	private Event_Dates $event_dates;

	/**
	 * Date_Filter constructor.
	 *
	 * @since TBD
	 *
	 * @param Event_Dates $event_dates The event's dates.
	 */
	public function __construct( Event_Dates $event_dates ) {
		$this->event_dates = $event_dates;
	}

	/**
	 * Returns the dates the request chose: null for every date, as when none or one not offered is chosen.
	 *
	 * @since TBD
	 *
	 * @param array<int,string> $dates The dates offered: the start by the date's post ID.
	 *
	 * @return int[]|null The chosen dates' post IDs, empty when the upcoming dates are chosen and there are none.
	 */
	public function chosen( array $dates ): ?array {
		$value = (string) tribe_get_request_var( self::NAME );

		if ( self::UPCOMING === $value ) {
			return array_keys( $this->event_dates->upcoming( $dates ) );
		}

		$date_id = absint( $value );

		return isset( $dates[ $date_id ] ) ? [ $date_id ] : null;
	}

	/**
	 * Returns the labelled select of the dates, with the chosen one selected, and its button.
	 *
	 * @since TBD
	 *
	 * @param array<int,string> $dates   The dates offered: the start by the date's post ID.
	 * @param int[]             $removed The dates that are gone.
	 * @param string            $label   The select's visible label.
	 *
	 * @return string The select's HTML.
	 */
	public function render( array $dates, array $removed, string $label ): string {
		$value    = (string) tribe_get_request_var( self::NAME );
		$options  = sprintf( '<option value="">%s</option>', esc_html__( 'All event dates', 'event-tickets' ) );
		$options .= sprintf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( self::UPCOMING ), selected( $value, self::UPCOMING, false ), esc_html__( 'All upcoming dates', 'event-tickets' ) );
		foreach ( $dates as $date_id => $start ) {
			$text = tribe_format_date( $start, true, tribe_get_datetime_format( true ) );

			if ( in_array( $date_id, $removed, true ) ) {
				// translators: %s is the date and time of an event date that no longer exists.
				$text = sprintf( _x( '%s (removed)', 'A recurring event date that no longer exists.', 'event-tickets' ), $text );
			}

			$options .= sprintf( '<option value="%1$d"%2$s>%3$s</option>', $date_id, selected( $value, (string) $date_id, false ), esc_html( $text ) );
		}

		return sprintf(
			'<label class="alignleft tec-tickets__recurring-date-filter-label" for="%1$s">%2$s</label><select name="%1$s" id="%1$s">%3$s</select><input type="submit" class="button action" value="%4$s">',
			esc_attr( self::NAME ),
			esc_html( $label ),
			$options,
			esc_attr__( 'Filter', 'event-tickets' )
		);
	}
}
