<?php
/**
 * Coupon Table class for displaying coupon data in a WordPress table.
 *
 * This class extends the Order_Modifier_Table and customizes it to display coupon data.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Order_Modifiers\Table_Views
 */

namespace TEC\Tickets\Order_Modifiers\Table_Views;

use TEC\Tickets\Order_Modifiers\Modifiers\Modifier_Strategy_Interface;

/**
 * Class Coupon_Table to display coupon data in a WordPress table.
 *
 * @since TBD
 */
class Coupon_Table extends Order_Modifier_Table {

	/**
	 * Modifier class for the table (Coupon in this case).
	 *
	 * @since TBD
	 *
	 * @var Modifier_Strategy_Interface
	 */
	protected $modifier;

	/**
	 * Constructor for the Coupon Table.
	 *
	 * @since TBD
	 *
	 * @param Modifier_Strategy_Interface $modifier The modifier class to use for data fetching and logic.
	 */
	public function __construct( Modifier_Strategy_Interface $modifier ) {
		$this->modifier = $modifier;

		parent::__construct();
	}

	/**
	 * Define the columns for the Coupon table.
	 *
	 * @since TBD
	 *
	 * @return array An array of columns.
	 */
	public function get_columns(): array {
		return [
			'display_name'     => __( 'Coupon Name', 'event-tickets' ),
			'slug'             => __( 'Code', 'event-tickets' ),
			'fee_amount_cents' => __( 'Amount', 'event-tickets' ),
			'used'             => __( 'Used', 'event-tickets' ),
			'remaining'        => __( 'Remaining', 'event-tickets' ),
			'status'           => __( 'Status', 'event-tickets' ),
		];
	}

	/**
	 * Fetch coupon data for the table.
	 *
	 * @since TBD
	 *
	 * @param string $search Search term.
	 * @param string $orderby Column to order by.
	 * @param string $order Sorting order.
	 *
	 * @return array The data for the table.
	 */
	protected function get_modifier_data( string $search, string $orderby, string $order ): array {
		$data = $this->modifier->find_by_search(
			[
				'search_term' => $search,
				'orderby'     => $orderby,
				'order'       => $order,
			]
		);

		// Ensure data is returned as an array of arrays.
		return is_array( $data ) ? $data : [];
	}

	/**
	 * Define the default sort column for Coupons.
	 *
	 * @since TBD
	 *
	 * @return string The default sort column.
	 */
	protected function get_default_sort_column(): string {
		return 'display_name';
	}

	/**
	 * Define sortable columns.
	 *
	 * @since TBD
	 *
	 * @return array An array of sortable columns.
	 */
	protected function get_sortable_columns(): array {
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
	protected function column_default( $item, $column_name ): string {
		// Special handling for "used" and "remaining" columns to output a dash if empty.
		if ( in_array( $column_name, [ 'used', 'remaining' ] ) ) {
			$value = isset( $item->$column_name ) && ! empty( $item->$column_name ) ? $item->$column_name : '-';
		} else {
			$value = isset( $item->$column_name ) ? $item->$column_name : '';
		}

		// Pass the modifier class to the filter.
		return apply_filters( 'tribe_events_tickets_order_modifiers_table_column', $value, $item, $column_name, $this->modifier );
	}

	/**
	 * Render the "Coupon Name" column with Edit | Delete actions.
	 *
	 * @since TBD
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	protected function column_display_name( $item ): string {
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
	protected function get_table_classes(): array {
		$classes = [ 'widefat', 'striped', 'coupons', 'tribe-coupons' ];

		/**
		 * Filters the default classes added to the coupons table `WP_List_Table`.
		 *
		 * @since TBD
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		return apply_filters( 'tec_coupons_table_classes', $classes, $this->modifier );
	}

	/**
	 * Message to be displayed when there are no items.
	 *
	 * @since TBD
	 */
	public function no_items() {
		esc_html_e( 'No coupons found.', 'event-tickets' );
	}
}
