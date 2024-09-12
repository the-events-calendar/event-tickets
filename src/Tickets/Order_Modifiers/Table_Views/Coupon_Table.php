<?php
/**
 * Class Order_Modifier_Table to display coupon/fee data in a WordPress table.
 *
 * @since TBD
 */

namespace TEC\Tickets\Order_Modifiers\Table_Views;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/screen.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

use WP_List_Table;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;

class Coupon_Table extends WP_List_Table {

	/**
	 * Modifier class for the table (e.g., Coupon or Fee).
	 *
	 * @since TBD
	 *
	 * @var Modifier_Strategy_Interface
	 */
	protected $modifier;

	/**
	 * Constructor for the Order Modifier Table.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Strategy_Interface $modifier The modifier class to use for data fetching and logic.
	 */
	public function __construct( Modifier_Strategy_Interface $modifier ) {
		$this->modifier = $modifier;

		parent::__construct(
			[
				'singular' => __( $modifier->get_modifier_type(), 'event-tickets' ),
				'plural'   => __( $modifier->get_modifier_type() . 's', 'event-tickets' ),
				'ajax'     => false,
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
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'display_name';
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'asc';

		// Fetch the data from the modifier class, including sorting.
		$data = $this->modifier->find_by_search(
			[
				'search_term' => $search,
				'orderby'     => $orderby,
				'order'       => $order,
			]
		);

		// Make sure data is returned as an array of arrays.
		if ( ! is_array( $data ) || empty( $data ) ) {
			$data = [];
		}

		// Pagination.
		$per_page     = $this->get_items_per_page( $this->modifier->get_modifier_type() . '_per_page', 10 );
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
	 * Define the columns for the table.
	 *
	 * @since TBD
	 *
	 * @return array An array of columns.
	 */
	public function get_columns() {
		// Example for Coupon/Fees columns.
		return [
			'display_name'     => __( 'Name', 'event-tickets' ),
			'slug'             => __( 'Code', 'event-tickets' ),
			'fee_amount_cents' => __( 'Amount', 'event-tickets' ),
			'used'             => __( 'Used', 'event-tickets' ),
			'remaining'        => __( 'Remaining', 'event-tickets' ),
			'status'           => __( 'Status', 'event-tickets' ),
		];
	}

	/**
	 * Define sortable columns.
	 *
	 * @since TBD
	 *
	 * @return array An array of sortable columns.
	 */
	protected function get_sortable_columns() {
		return [
			'display_name'     => [ 'display_name', true ],
			'slug'             => [ 'slug', false ],
			'fee_amount_cents' => [ 'fee_amount_cents', false ],
			'used'             => [ 'used', false ],
			'remaining'        => [ 'remaining', false ],
			'status'           => [ 'status', false ],
		];
	}

	/**
	 * Render the default column.
	 *
	 * @since TBD
	 *
	 * @param array  $item The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		$value = empty( $item->$column_name ) ? '' : $item->$column_name;

		// Pass the modifier class to the filter.
		return apply_filters( 'tribe_events_tickets_order_modifiers_table_column', $value, $item, $column_name, $this->modifier );
	}

	/**
	 * Render the "Name" column with Edit | Delete actions.
	 *
	 * @since TBD
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	protected function column_display_name( $item ) {
		$edit_link   = '#'; // Replace with the actual link to the edit screen.
		$delete_link = '#'; // Replace with the actual link to delete functionality.

		$actions = [
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), __( 'Edit', 'event-tickets' ) ),
			'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $delete_link ), __( 'Delete', 'event-tickets' ) ),
		];

		return sprintf( '%1$s %2$s', esc_html( $item->display_name ), $this->row_actions( $actions ) );
	}

	/**
	 * Adds search functionality to the table.
	 *
	 * @since TBD
	 *
	 * @param string $text The text to display.
	 * @param string $input_id The input ID.
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		echo '<p class="search-box">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . '</label>';
		echo '<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="' . esc_attr( $_REQUEST['s'] ?? '' ) . '" />';
		submit_button( $text, '', '', false );
		echo '</p>';
	}

	/**
	 * Overrides the list of CSS classes for the WP_List_Table table tag.
	 *
	 * @since TBD
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$classes = [ 'widefat', 'striped', 'order-modifiers', 'tribe-order-modifiers' ];

		/**
		 * Filters the default classes added to the table `WP_List_Table`.
		 *
		 * @since TBD
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		return apply_filters( 'tec_order_modifiers_table_classes', $classes, $this->modifier );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 * @since TBD
	 */
	public function no_items() {
		esc_html_e( 'No modifiers found.', 'event-tickets' );
	}
}
