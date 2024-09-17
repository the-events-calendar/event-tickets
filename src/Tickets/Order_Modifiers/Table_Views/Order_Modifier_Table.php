<?php

namespace TEC\Tickets\Order_Modifiers\Table_Views;

use WP_List_Table;
use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;

/**
 * Abstract class for Order Modifier Table (Coupons/Fees).
 *
 * @since TBD
 */
abstract class Order_Modifier_Table extends WP_List_Table {

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
	 * Render the default column.
	 *
	 * This method dynamically calls a dedicated method to render the specific column.
	 * If no specific method exists for the column, it falls back to a generic column handler.
	 *
	 * @since TBD
	 *
	 * @param object $item The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		// Build the method name dynamically based on the column name.
		$method = 'render_' . $column_name . '_column';

		// If a specific method exists for the column, call it.
		if ( method_exists( $this, $method ) ) {
			return $this->$method( $item );
		}

		// Fallback to a default rendering method if no specific method is found.
		return $this->render_generic_column( $item, $column_name );
	}

	/**
	 * Fallback method for rendering generic columns.
	 *
	 * @since TBD
	 *
	 * @param object $item The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	protected function render_generic_column( $item, $column_name ) {
		return ! empty( $item->$column_name ) ? esc_html( $item->$column_name ) : '-';
	}

	/**
	 * Helper to render actions for a column. The `edit` action is used for the label link as well.
	 *
	 * @since TBD
	 *
	 * @param string $label The display label for the item (e.g., the name of the coupon or fee).
	 * @param array  $actions Array of actions, where the key is a readable action label (e.g., 'Edit', 'Delete')
	 *                        and the value is the URL.
	 *
	 * @return string Rendered HTML for actions.
	 */
	protected function render_actions( string $label, array $actions ): string {
		$action_links = [];
		$label_html   = esc_html( $label );

		// Loop through the actions and build both the label and action links.
		foreach ( $actions as $action_label => $url ) {
			$url            = esc_url( $url );
			$action_links[] = sprintf( '<a href="%s">%s</a>', $url, esc_html( $action_label ) );

			// If 'Edit' is found, also make the label a link to the edit action.
			if ( strtolower( $action_label ) === 'edit' ) {
				$label_html = sprintf( '<a href="%s">%s</a>', $url, esc_html( $label ) );
			}
		}

		// Join the action links and append them to the label with the row actions.
		return sprintf( '%1$s %2$s', $label_html, $this->row_actions( $action_links ) );
	}

	/**
	 * Adds a search box with a custom placeholder to the table.
	 *
	 * @since TBD
	 *
	 * @param string $text The text to display in the submit button.
	 * @param string $input_id The input ID.
	 * @param string $placeholder The placeholder text to display in the search input.
	 *
	 * @return void
	 */
	public function search_box( $text, $input_id, $placeholder = '' ) {
		if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
			return;
		}

		$input_id = $input_id . '-search-input';

		echo '<p class="search-box">';
		echo '<label class="screen-reader-text" for="' . esc_attr( $input_id ) . '">' . esc_html( $text ) . '</label>';
		echo '<input type="search" id="' . esc_attr( $input_id ) . '" name="s" value="' . esc_attr( $_REQUEST['s'] ?? '' ) . '" placeholder="' . esc_attr( $placeholder ) . '" />';
		submit_button( $text, '', '', false );
		echo '</p>';
	}
}
