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
	protected $supported_columns = [ 'tickets' => 'render_tickets_entry' ];

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
	 * @since 5.5.11 Moved to shorthand array syntax.
	 *
	 * @param array $columns
	 *
	 * @return array
	 */
	public function filter_manage_post_columns( array $columns = [] ) {
		$additional_columns = [];

		if ( isset( $this->supported_columns['tickets'] ) ) {
			$additional_columns['tickets'] = __( 'Attendees', 'event-tickets' );
		}

		return $columns + $additional_columns;
	}

	/**
	 * Renders a column content calling the right render method.
	 *
	 * @param string $column  The current column name.
	 * @param int $post_id The current post ID.
	 *
	 * @return bool Whether the column content was rendered or not if the column
	 *              type is not supported.
	 */
	public function render_column( string $column, int $post_id ): bool {
		if ( ! isset( $this->supported_columns[ $column ] ) ) {
			return false;
		}

		$method = $this->supported_columns[ $column ];


		/**
		 * Filter whether a column should be displayed in the admin post type table.
		 *
		 * @since 5.5.11
		 *
		 * @param bool   $should_display_column Whether the column should be displayed or not. Default true.
		 * @param string $column                The current column name.
		 * @param int    $post_id               The current post ID.
		 * @param object $instance              The current instance of the plugin class.
		 */
		$should_display_column = apply_filters( 'tec_tickets_admin_post_type_table_column', true, $column, $post_id, $this );
		if ( ! $should_display_column ) {
			return false;
		}

		/**
		 * Filter whether a specific column should be displayed in the admin post type table.
		 *
		 * The dynamic portion of the hook name, `$column`, refers to the current column name. Example, tickets
		 *
		 * @since 5.5.11
		 *
		 * @param bool   $should_display_column Whether the column should be displayed or not. Default true.
		 * @param string $column                The current column name.
		 * @param int    $post_id               The current post ID.
		 * @param object $instance              The current instance of the plugin class.
		 */
		$should_display_column = apply_filters( "tec_tickets_admin_post_type_table_column_{$column}", $should_display_column, $column, $post_id, $this );
		if ( ! $should_display_column ) {
			return false;
		}

		echo call_user_func( [ $this, $method ], $post_id );

		return true;
	}

	/**
	 * Renders the content of the "Attendee" column.
	 *
	 * @param int $post_id The current post ID.
	 *
	 * @return string The column HTML.
	 */
	protected function render_tickets_entry( int $post_id ): string {
		$post = get_post( $post_id );

		$total = Tribe__Tickets__Tickets::get_event_attendees_count( $post_id );

		// Remove the "Not Going" RSVPs
		$not_going = tribe( 'tickets.rsvp' )->get_attendees_count_not_going( $post_id );

		// Bail with —
		if ( $not_going >= $total ) {
			return '&mdash;';
		}

		$percentage = $this->get_percentage_string( $post_id, null, $total, $not_going );
		$content    = sprintf( '<div>%s</div>%s', $total - $not_going, $percentage );
		$attendees  = tribe( 'tickets.attendees' );

		if ( ! $attendees->can_access_page( $post_id ) ) {
			return $content;
		}

		$attendees_link = $attendees->get_report_link( $post );

		return sprintf( '<a href="%s" class="tribe-tickets-column-attendees-link">%s</a>', $attendees_link, $content );
	}

	/**
	 * Gets the HTML for the percentage string for Attendees Column
	 *
	 * @since 4.6.2 Deprecated the second parameter.
	 * @since 4.10.6 Added $total and $not_going parameters to further optimize requests.
	 * @since 5.5.11 Added caching and reorganized code to further optimize requests.
	 *
	 * @param int $post_id   The current post ID.
	 * @param null    $deprecated
	 * @param int|null $total     Total attendees found for post (if already calculated).
	 * @param int|null $not_going Total attendees not going for post (if already calculated).
	 *
	 * @return string The percentage HTML or an empty string if one of the
	 *                post tickets has unlimited stock.
	 */
	protected function get_percentage_string( int $post_id, $deprecated = null, int $total = null, int $not_going = null ): string {

		/** @var Tribe__Cache $cache */
		$cache = tribe( 'cache' );
		$key   = __METHOD__ . '-' . $post_id . '_' . (int) $total . '_' . (int) $not_going;

		if ( isset( $cache[ $key ] ) ) {
			return $cache[ $key ];
		}

		/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$ticket = $tickets_handler->get_post_totals( $post_id );

		if ( null === $total ) {
			$total = Tribe__Tickets__Tickets::get_event_attendees_count( $post_id );
		}

		if ( null === $not_going ) {
			$not_going = tribe( 'tickets.rsvp' )->get_attendees_count_not_going( $post_id );
		}

		// Remove the "Not Going" RSVPs
		$total -= $not_going;

		// Bail early for unlimited
		if ( $ticket['has_unlimited'] ) {

			$unlimited_amount = tribe_tickets_get_readable_amount( - 1 );

			// Set our cache to the unlimited text.
			$cache[ $key ] = $unlimited_amount;

			return $unlimited_amount;
		}

		$stock = $ticket['stock'];

		if ( $ticket['has_shared'] ) {
			$shared_capacity_obj = new Tribe__Tickets__Global_Stock( $post_id );
			$global_stock        = $shared_capacity_obj->get_stock_level();
			$stock               = $global_stock >= $ticket['stock'] ? $global_stock : $ticket['stock'];
		}

		if ( 1 > $total || 0 === $ticket['capacity'] ) {
			// If there have been zero sales we need not do any further arithmetic
			$percentage = 0;
		} elseif ( 0 === $stock ) {
			// If $stock is zero (and items *have* been sold per the above conditional) we can assume 100%
			$percentage = 100;
		} else {
			// In all other cases, calculate the actual percentage
			$percentage = round( ( 100 / $ticket['capacity'] ) * $total );
		}

		// Set our cache to the percentage.
		$cache[ $key ] = $percentage;

		// To escape percent in sprintf use %%.
		return sprintf( '<div><small>(%s%%)</small></div>', $percentage );
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
