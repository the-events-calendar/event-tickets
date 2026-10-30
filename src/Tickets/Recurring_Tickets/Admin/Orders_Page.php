<?php
/**
 * The orders of a recurring event's dates, on the event's Orders page.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */

namespace TEC\Tickets\Recurring_Tickets\Admin;

use TEC\Tickets\Recurring_Tickets\Repositories\Tickets as Rows;
use Tribe__Date_Utils as Dates;

/**
 * Lists a recurring event's orders of every date on the event's Orders page and its export, gives each ordered
 * ticket its date, narrows the list to the dates chosen and sums the sales over every date and on the next.
 *
 * An order of a row holds its date's ID; the event's Orders page looks orders up by the event's own ID.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Recurring_Tickets\Admin
 */
final class Orders_Page {
	/**
	 * An event ID no order holds: narrowing to it lists no order, where no event at all would list every one.
	 *
	 * @since TBD
	 *
	 * @var int
	 */
	private const NO_EVENT = -1;

	/**
	 * The rows repository.
	 *
	 * @since TBD
	 *
	 * @var Rows
	 */
	private Rows $rows;

	/**
	 * The event's dates.
	 *
	 * @since TBD
	 *
	 * @var Event_Dates
	 */
	private Event_Dates $event_dates;

	/**
	 * The select that narrows the table to one date.
	 *
	 * @since TBD
	 *
	 * @var Date_Filter
	 */
	private Date_Filter $date_filter;

	/**
	 * The sales of each recurring event ticket over every date.
	 *
	 * @since TBD
	 *
	 * @var Template_Sales
	 */
	private Template_Sales $template_sales;

	/**
	 * Orders_Page constructor.
	 *
	 * @since TBD
	 *
	 * @param Rows           $rows           The rows repository.
	 * @param Event_Dates    $event_dates    The event's dates.
	 * @param Date_Filter    $date_filter    The select that narrows the table to one date.
	 * @param Template_Sales $template_sales The sales of each recurring event ticket over every date.
	 */
	public function __construct( Rows $rows, Event_Dates $event_dates, Date_Filter $date_filter, Template_Sales $template_sales ) {
		$this->rows           = $rows;
		$this->event_dates    = $event_dates;
		$this->date_filter    = $date_filter;
		$this->template_sales = $template_sales;
	}

	/**
	 * Looks an event's orders up by its dates too, or by those chosen.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $arguments The Orders page's, or its export's, query arguments.
	 *
	 * @return array<string,mixed> The arguments.
	 */
	public function filter_events( $arguments ): array {
		$arguments = (array) $arguments;
		$event_id  = is_numeric( $arguments['events'] ?? null ) ? (int) $arguments['events'] : 0;
		$dates     = $this->event_dates->all( $event_id );

		if ( ! $dates ) {
			return $arguments;
		}

		$chosen              = $this->date_filter->chosen( $dates );
		$arguments['events'] = null === $chosen ? array_merge( [ $event_id ], array_keys( $dates ) ) : ( $chosen ?: [ self::NO_EVENT ] );

		return $arguments;
	}

	/**
	 * Gives an ordered recurring event ticket, in the orders table, the date it was bought for.
	 *
	 * @since TBD
	 *
	 * @param string|mixed              $name      The ticket's name.
	 * @param array<string,mixed>|mixed $cart_item The order's line of the ticket.
	 *
	 * @return string|mixed The name.
	 */
	public function add_date_to_purchased( $name, $cart_item ) {
		$dates = $this->event_dates->all( $this->event_dates->event_of( $this->page_event_id() ) );
		$start = $dates[ (int) ( ( (array) $cart_item )['event_id'] ?? 0 ) ] ?? '';

		if ( '' === $start ) {
			return $name;
		}

		return sprintf( '%1$s - %2$s', $name, tribe_format_date( $start, false, Dates::datepicker_formats( Dates::get_datepicker_format_index() ) ) );
	}

	/**
	 * Prints, above the orders table, a select of the event's dates to narrow the list to them.
	 *
	 * @since TBD
	 *
	 * @param int|mixed $post_id The post the orders are of.
	 *
	 * @return void
	 */
	public function render_date_filter( $post_id ): void {
		$event_id = (int) $post_id;
		$dates    = $this->event_dates->all( $event_id );

		if ( ! $dates ) {
			return;
		}

		// phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped -- Escaped as it is built.
		echo $this->date_filter->render( $dates, $this->event_dates->removed( $event_id ), __( 'Orders for:', 'event-tickets' ) );
	}

	/**
	 * Gives a recurring event's Orders page, and a date's, the summary of the sales of every date and of the next.
	 *
	 * @since TBD
	 *
	 * @param array<string,mixed>|mixed $vars The Orders page's template variables.
	 *
	 * @return array<string,mixed> The variables.
	 */
	public function summarize( $vars ): array {
		$vars     = (array) $vars;
		$post_id  = (int) ( $vars['post_id'] ?? 0 );
		$event_id = $this->event_dates->event_of( $post_id );
		$dates    = $this->event_dates->all( $event_id );

		if ( ! $dates ) {
			return $vars;
		}

		$next_date = $this->event_dates->next( $event_id );
		$summary   = new Order_Summary( $event_id, $this->rows, $this->template_sales, $dates, $next_date, $this->date_filter->label( $next_date ) );
		$summary->init();

		$vars['order_summary']       = $summary;
		$vars['post_singular_label'] = __( 'Recurring Event', 'event-tickets' );

		return $vars;
	}

	/**
	 * Prints, above the sales totals of every date, those of the next date.
	 *
	 * @since TBD
	 *
	 * @param object|mixed $summary The Orders page's summary.
	 *
	 * @return void
	 */
	public function render_next_date( $summary ): void {
		if ( ! $summary instanceof Order_Summary || ! $summary->get_next_date() ) {
			return;
		}

		tribe( 'tickets.admin.views' )->template(
			'recurring-tickets/orders/next-date',
			[
				// translators: %s is the date the sales are of, e.g. "October 22".
				'title' => sprintf( __( 'Next Date: %s', 'event-tickets' ), $summary->get_next_date_label() ),
				'sales' => $summary->get_next_date_sales(),
			]
		);
	}

	/**
	 * Returns the event of the Orders page, as the page reads it.
	 *
	 * @since TBD
	 *
	 * @return int The event's post ID.
	 */
	private function page_event_id(): int {
		return (int) tribe_get_request_var( 'event_id', tribe_get_request_var( 'post_id', 0 ) );
	}
}
