<?php
/**
 * The list table for the All Tickets screen.
 *
 * @since  TBD
 *
 * @package TEC\Tickets\Admin
 */

namespace TEC\Tickets\Admin\All_Tickets;

use WP_List_Table;

class List_Table extends WP_List_Table {
	/**
	 * The constructor.
	 *
	 * @since  TBD
	 */
	public function __construct() {
		parent::__construct( [
			'singular' => 'ticket',
			'plural'   => 'tickets',
			'ajax'     => false,
		] );
	}

	/**
	 * Returns the columns for the list table.
	 *
	 * @since  TBD
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'cb'       => '<input type="checkbox" />',
			'name'     => esc_html__( 'Name', 'event-tickets' ),
			'quantity' => esc_html__( 'Quantity', 'event-tickets' ),
			'price'    => esc_html__( 'Price', 'event-tickets' ),
			'actions'  => esc_html__( 'Actions', 'event-tickets' ),
		];
	}

	/**
	 * Renders the default column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 * @param string $column_name The current column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		return '';
	}

	/**
	 * Renders the checkbox column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" />';
	}

	/**
	 * Renders the name column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_name( $item ) {
		return esc_html__( 'Ticket Name', 'event-tickets' );
	}

	/**
	 * Renders the quantity column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_quantity( $item ) {
		return esc_html__( 'Ticket Quantity', 'event-tickets' );
	}

	/**
	 * Renders the price column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_price( $item ) {
		return esc_html__( 'Ticket Price', 'event-tickets' );
	}

	/**
	 * Renders the actions column.
	 *
	 * @since  TBD
	 *
	 * @param object $item The current item.
	 */
	public function column_actions( $item ) {
		return esc_html__( 'Ticket Actions', 'event-tickets' );
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 5.8.4 Adding caching to eliminate method running multiple times.
	 */
	public function prepare_items() {
		$this->process_actions();

		$current_page = $this->get_pagenum();
		$per_page     = $this->get_items_per_page( $this->per_page_option );

		$pagination_args = [
			'total_items' => 0,
			'per_page'    => $per_page,
		];

		$args = [
			'page'               => $current_page,
			'per_page'           => $per_page,
			'return_total_found' => true,
			'order'              => 'DESC',
		];

		// Setup sorting args.
		if ( tribe_get_request_var( 'orderby' ) ) {
			$args['orderby'] = tribe_get_request_var( 'orderby' );
		}

		if ( tribe_get_request_var( 'order' ) ) {
			$args['order']   = tribe_get_request_var( 'order' );
		}

		/**
		 * Filters the arguments used to query the tickets for the All Tickets Table.
		 *
		 * @since 5.9.1
		 *
		 * @param array $args The arguments used to query the tickets for the All Tickets Table.
		 *
		 * @return array
		 */
		$args = apply_filters( 'tec_tickets_all_tickets_table_query_args', $args );

		// $item_data = Tribe__Tickets__Tickets::get_attendees_by_args( $args );

		$items = [];

		// if ( ! empty( $item_data ) ) {
		// 	$items = $item_data['attendees'];

		// 	$pagination_args['total_items'] = $item_data['total_found'];
		// }

		$this->items = $items;

		$this->set_pagination_args( $pagination_args );
	}
}{
    }
