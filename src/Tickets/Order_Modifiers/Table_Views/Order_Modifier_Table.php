<?php

namespace TEC\Tickets\Order_Modifiers\Table_Views;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use WP_List_Table;

abstract class Order_Modifier_Table extends WP_List_Table {

	/**
	 * Constructor for the Order Modifier Table.
	 *
	 * @since TBD
	 */
	public function __construct() {
		parent::__construct(
			[
				'ajax' => false,
			]
		);
	}

	/**
	 * Prepares the items for display in the table.
	 *
	 * @since TBD
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		// Handle search.
		$search = isset( $_REQUEST['s'] ) ? sanitize_text_field( $_REQUEST['s'] ) : '';

		// Capture sorting parameters.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : $this->get_default_sort_column();
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'asc';

		// Fetch the data from the specific modifier logic (Coupons/Fees).
		$data = $this->get_modifier_data( $search, $orderby, $order );

		// Pagination.
		$per_page     = $this->get_items_per_page( 'items_per_page', 10 );
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$data = array_slice( $data, ( $current_page - 1 ) * $per_page, $per_page );

		// Set the items for the table.
		$this->items = $data;

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			]
		);
	}

	/**
	 * Helper function to render Edit/Delete actions for a column.
	 *
	 * @since TBD
	 *
	 * @param string $edit_link The link for editing.
	 * @param string $delete_link The link for deleting.
	 *
	 * @return string The HTML for the actions.
	 */
	protected function render_actions( $edit_link, $delete_link ) {
		$actions = [
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Edit', 'event-tickets' ) ),
			'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $delete_link ), __( 'Delete', 'event-tickets' ) ),
		];

		return $this->row_actions( $actions );
	}

	/**
	 * Method to get data for the table, to be implemented by extending classes.
	 *
	 * @since TBD
	 *
	 * @param string $search Search term.
	 * @param string $orderby Column to order by.
	 * @param string $order Sorting order.
	 *
	 * @return array The data for the table.
	 */
	abstract protected function get_modifier_data( string $search, string $orderby, string $order ): array;

	/**
	 * Define the default sort column, to be implemented by extending classes.
	 *
	 * @since TBD
	 *
	 * @return string The default sort column.
	 */
	abstract protected function get_default_sort_column(): string;

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since TBD
	 */
	public function no_items() {
		esc_html_e( 'No modifiers found.', 'event-tickets' );
	}

	/**
	 * Search box.
	 *
	 * @since TBD
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		echo '<p class="search-box">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . '</label>';
		echo '<input type="search" id="' . esc_attr( $input_id . '-search-input' ) . '" name="s" value="' . esc_attr( $_REQUEST['s'] ?? '' ) . '" />';
		submit_button( $text, '', '', false );
		echo '</p>';
	}
}
