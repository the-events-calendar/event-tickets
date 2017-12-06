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

		/**
		 * Provides a convenient way to control additional ticket specific admin
		 * columns used in the WP Post list tables.
		 *
		 * To remove all currently supported columns (currently, there is only the
		 * "attendees" column) for instance once can then do:
		 *
		 *     add_filter( 'tribe_tickets_supported_admin_columns', '__return_empty_array' );
		 *
		 * @since 4.4.9
		 *
		 * @param array $supported_columns
		 */
		$this->supported_columns = (array) apply_filters( 'tribe_tickets_supported_admin_columns', $this->supported_columns );
	}

	/**
	 * Filters the list columns to add the ticket related ones.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function filter_manage_post_columns( array $columns = array() ) {
		$additional_columns = array();

		if ( isset( $this->supported_columns['tickets'] ) ) {
			$additional_columns['tickets'] = __( 'Attendees', 'event-tickets' );
		}

		return $columns + $additional_columns;
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
		$post = get_post( $post_id );
		$totals = tribe( 'tickets.handler' )->get_post_totals( $post );

		// Bail with —
		if ( 0 === $totals['tickets'] ) {
			return '&mdash;';
		}

		$content = sprintf( '<div>%s</div>%s', $totals['sold'], $this->get_percentage_string( $post->ID ) );
		$attendees_link = tribe( 'tickets.attendees' )->get_report_link( $post );

		return sprintf( '<a href="%s" target="_blank" class="tribe-tickets-column-attendees-link">%s</a>', $attendees_link, $content );
	}

	/**
	 * Gets the HTML for the percentage string for Attendees Column
	 *
	 * @since  4.6.2  Deprecated the Second Param
	 *
	 * @param  int  $post_id    The current post ID.
	 * @param  null $deprecated
	 *
	 * @return string The percentage HTML or an empty string if one of the
	 *                post tickets has unlimited stock.
	 */
	protected function get_percentage_string( $post_id, $deprecated = null ) {
		$totals = tribe( 'tickets.handler' )->get_post_totals( $post_id );

		// Bail early for unlimited
		if ( $totals['has_unlimited'] ) {
			return tribe_tickets_get_readable_amount( -1 );
		}

		$shared_capacity_obj = new Tribe__Tickets__Global_Stock( $post_id );
		$global_stock_enabled = $shared_capacity_obj->is_enabled();
		$global_stock = $shared_capacity_obj->get_stock_level();

		$stock = $global_stock_enabled ? $global_stock : $totals['stock'];

		// If there have been zero sales we need not do any further arithmetic
		if ( 0 === $totals['sold'] || 0 === $totals['capacity'] ) {
			$percentage = 0;
		}
		// If $stock is zero (and items *have* been sold per the above conditional) we can assume 100%
		elseif ( 0 === $stock ) {
			$percentage = 100;
		}
		// In all other cases, calculate the actual percentage
		else {
			$percentage = round( ( 100 / $totals['capacity'] ) * $totals['sold'], 0 );
		}

		return ' <div><small>(' . $percentage . '%)</small></div>';
	}

	/************************
	 *                      *
	 *  Deprecated Methods  *
	 *                      *
	 ************************/
	// @codingStandardsIgnoreStart

	/**
	 * Iterates over an array of tickets to fetch the sale total.
	 *
	 * @deprecated 4.6.2
	 *
	 * @param Tribe__Tickets__Ticket_Object[] $tickets
	 *
	 * @return int The sale total.
	 */
	protected function get_sold( $tickets ) {
		_deprecated_function( __METHOD__, '4.6.2', 'tribe( "tickets.handler" )->get_ticket_totals()' );
		$sold = 0;

		/** @var Tribe__Tickets__Ticket_Object $ticket */
		foreach ( $tickets as $ticket ) {
			$sold += $ticket->qty_sold();
		}

		return $sold;
	}
	// @codingStandardsIgnoreEnd

}
