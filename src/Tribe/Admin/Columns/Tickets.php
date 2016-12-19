<?php


/**
 * Class Tribe__Tickets__Admin__Columns__Tickets
 *
 * Adds additional tickets related columns to the list table.
 */
class Tribe__Tickets__Admin__Columns__Tickets {

	/**
	 * @var string
	 */
	protected $post_type;

	/**
	 * @var array A map that related supported columns to the methods used to render
	 *            their content.
	 */
	protected $supported_columns = array( 'tickets' => 'render_tickets_entry' );

	/**
	 * Tribe__Tickets__Admin__Columns__Tickets constructor.
	 *
	 * @param string $post_type
	 */
	public function __construct( $post_type = 'post' ) {
		$this->post_type = $post_type;
	}

	/**
	 * Filters the list columns to add the ticket related ones.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function filter_manage_post_columns( array $columns = array() ) {
		$columns['tickets'] = __( 'Attendees', 'event-tickets' );

		return $columns;
	}

	/**
	 * Renders a column content calling the right render method.
	 *
	 * @param string $column  The current column name.
	 * @param int    $post_id The current post ID.
	 *
	 * @return bool Whether the column content was rendered or not if the column
	 *              type is not supported.
	 */
	public function render_column( $column, $post_id ) {
		if ( ! isset( $this->supported_columns[ $column ] ) ) {
			return false;
		}

		$method = $this->supported_columns[ $column ];

		echo call_user_func( array( $this, $method ), $post_id );

		return true;
	}

	/**
	 * Renders the content of the "Attendee" column.
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return string The column HTML.
	 */
	protected function render_tickets_entry( $post_id ) {
		$output = '—';

		$tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $post_id );
		if ( empty( $tickets ) ) {
			return '—';
		}

		$content = sprintf( '<div>%s</div>%s', $this->get_sold( $tickets ), $this->get_percentage_string( $tickets, $post_id ) );
		$attendees_link = tribe( 'tickets.handler' )->get_attendee_report_link( get_post( $post_id ) );

		return sprintf( '<a href="%s" target="_blank">%s</a>', $attendees_link, $content );
	}

	/**
	 * Iterates over an array of tickets to fetch the sale total.
	 *
	 * @param Tribe__Tickets__Ticket_Object[] $tickets
	 *
	 * @return int The sale total.
	 */
	protected function get_sold( $tickets ) {
		$sold = 0;

		/** @var Tribe__Tickets__Ticket_Object $ticket */
		foreach ( $tickets as $ticket ) {
			$sold += $ticket->qty_sold();
		}

		return $sold;
	}

	/**
	 * Iterates over an array of tickets to render the percentage HTML.
	 *
	 * @param Tribe__Tickets__Ticket_Object[] $tickets
	 * @param  int $post_id The current post ID.
	 *
	 * @return string The percentage HTML or an empty string if one of the
	 *                post tickets has unlimited stock.
	 */
	protected function get_percentage_string( array $tickets = array(), $post_id ) {
		$sold  = 0;
		$stock = 0;

		$global_stock_enabled = get_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED, true ) ==true;
		$global_stock = $global_stock_enabled ?
			get_post_meta( $post_id, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL, true )
			: false;

		/** @var Tribe__Tickets__Ticket_Object $ticket */
		foreach ( $tickets as $ticket ) {
			$remaining = $ticket->remaining();

			// not tracking stock
			if ( false === $remaining ) {
				// if event just one is not tracking stock bail
				return '';
			}

			$this_sold  = $ticket->qty_sold();
			$this_stock = $global_stock !== false
				? $global_stock
				: $ticket->stock() + $this_sold;

			// sanity check
			if ( $this_sold > $this_stock ) {
				$this_sold = $this_stock;
			}

			$sold += $this_sold;
			$stock += $this_stock;
		}

		$stock = $global_stock !== false ? $global_stock : $stock;

		// If there have been zero sales we need not do any further arithmetic
		if ( 0 === $sold ) {
			$percentage = 0;
		}
		// If $stock is zero (and items *have* been sold per the above conditional) we can assume 100%
		elseif ( 0 === $stock ) {
			$percentage = 100;
		}
		// In all other cases, calculate the actual percentage
		else {
			$percentage = round( $sold * 100 / $stock, 0 );
		}

		return ' <div><small>(' . $percentage . '%)</small></div>';
	}
}
