<?php

namespace TEC\Tickets\Order_Modifiers\Table_Views;

/**
 * Class for displaying Coupon data in the table.
 *
 * @since TBD
 */
class Coupon_Table extends Order_Modifier_Table {

	/**
	 * Define the columns for the table.
	 *
	 * @since TBD
	 *
	 * @return array An array of columns.
	 */
	public function get_columns() {
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
	 * Renders the "display_name" column with "Edit" and "Delete" actions, including nonces for security.
	 *
	 * This method generates the display content for the "Name" column, including an "Edit" link
	 * and the "Delete" link. The edit link directs the user to the admin page where
	 * they can edit the specific modifier, passing the necessary parameters for the page,
	 * modifier type, modifier ID, and a nonce for security.
	 *
	 * @since TBD
	 *
	 * @param object $item The current item from the table, typically an Order_Modifier object.
	 *
	 * @return string The HTML output for the "display_name" column, including row actions.
	 */
	protected function render_display_name_column( $item ) {
		$edit_link = add_query_arg(
			[
				'page'        => $this->modifier->get_page_slug(),
				'modifier'    => $this->modifier->get_modifier_type(),
				'edit'        => 1,
				'modifier_id' => $item->id,
				'_wpnonce'    => wp_create_nonce( 'edit_modifier_' . $item->id ),
			],
			admin_url( 'admin.php' )
		);

		// Replace with actual delete URL and include nonce.
		$delete_link = add_query_arg(
			[
				'action'      => 'delete_modifier',
				'modifier_id' => $item->id,
				'_wpnonce'    => wp_create_nonce( 'delete_modifier_' . $item->id ),
			],
			admin_url( 'admin.php' )
		);

		$actions = [
			__( 'Edit', 'event-tickets' )   => $edit_link,
			__( 'Delete', 'event-tickets' ) => $delete_link,
		];

		return $this->render_actions( $item->display_name, $actions );
	}

	/**
	 * Render the "status" column.
	 *
	 * @since TBD
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	protected function render_status_column( $item ) {
		return $this->modifier->get_status_display( $item->status );
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
}
