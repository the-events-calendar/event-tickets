<?php
/**
 * Coupon_Table class for displaying Coupon data in the table.
 *
 * This class defines the structure and behavior for rendering coupon-related data in a table format,
 * including columns for coupon name, code, amount, usage, and status. It extends the Order_Modifier_Table
 * class and provides specific logic for handling coupon-specific data display.
 *
 * @since 5.18.0
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers\Table_Views
 */
namespace TEC\Tickets\Commerce\Order_Modifiers\Table_Views;

use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons;

/**
 * Class for displaying Coupon data in the table.
 *
 * @since 5.18.0
 */
class Coupon_Table extends Order_Modifier_Table {

	/**
	 * Coupon_Table constructor.
	 *
	 * @param Coupon                      $modifier       The modifier strategy instance.
	 * @param Coupons                     $order_modifier The order modifier repository.
	 * @param Order_Modifiers_Meta        $order_modifier_meta_repository The order modifier meta repository.
	 * @param Order_Modifier_Relationship $order_modifier_relationship The order modifier relationship repository.
	 */
	public function __construct(
		Coupon $modifier,
		Coupons $order_modifier,
		Order_Modifiers_Meta $order_modifier_meta_repository,
		Order_Modifier_Relationship $order_modifier_relationship
	) {
		$this->modifier                  = $modifier;
		$this->order_modifier_repository = $order_modifier;
		parent::__construct( $modifier, $order_modifier_meta_repository, $order_modifier_relationship );
	}

	/**
	 * Define the columns for the table.
	 *
	 * @since 5.18.0
	 *
	 * @return array An array of columns.
	 */
	public function get_columns() {
		return [
			'display_name' => __( 'Coupon Name', 'event-tickets' ),
			'slug'         => __( 'Code', 'event-tickets' ),
			'raw_amount'   => __( 'Amount', 'event-tickets' ),
			'used'         => __( 'Used', 'event-tickets' ),
			'remaining'    => __( 'Remaining', 'event-tickets' ),
			'status'       => __( 'Status', 'event-tickets' ),
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
	 * @since 5.18.0
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
			],
			admin_url( 'admin.php' )
		);

		// Replace with actual delete URL and include nonce.
		$delete_link = add_query_arg(
			[
				'action'      => 'delete_modifier',
				'modifier_id' => $item->id,
				'_wpnonce'    => wp_create_nonce( 'delete_modifier_' . $item->id ),
				'modifier'    => $this->modifier->get_modifier_type(),
			],
			admin_url( 'admin.php' )
		);

		$actions = [
			'edit'   => [
				'label' => __( 'Edit', 'event-tickets' ),
				'url'   => $edit_link,
			],
			'delete' => [
				'label' => __( 'Delete', 'event-tickets' ),
				'url'   => $delete_link,
			],
		];

		return $this->render_actions( $item->display_name, $actions );
	}

	/**
	 * Render the "status" column.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	protected function render_status_column( $item ) {
		return $this->modifier->get_status_display( $item->status );
	}

	/**
	 * Retrieves the number of coupons available for a given order modifier.
	 *
	 * @since 5.18.0
	 *
	 * @param int $order_modifier_id The ID of the order modifier.
	 *
	 * @return int Returns the number of coupons available or 0 if none are set.
	 */
	protected function get_coupons_available( int $order_modifier_id ): int {
		$coupons_available_key = 'coupons_available';

		// Fetch the available coupons for the given order modifier.
		$number_available = $this->order_modifier_meta_repository->find_by_order_modifier_id_and_meta_key( $order_modifier_id, $coupons_available_key );

		// Return the available number, or 0 if it's not set or is empty.
		return ! empty( $number_available->meta_value ) ? (int) $number_available->meta_value : 0;
	}

	/**
	 * Renders the remaining column for an order modifier.
	 *
	 * Displays the number of remaining coupons by subtracting the number used from the available coupons.
	 * If the coupons_available is empty or 0, returns '-'.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The order modifier item.
	 *
	 * @return string The number of remaining coupons, or '-' if unlimited.
	 */
	protected function render_remaining_column( $item ): string {
		$order_modifier_id = $item->id;

		// Fetch available and used coupons.
		$coupons_available = $this->get_coupons_available( $order_modifier_id );
		$coupons_uses_key  = 'coupons_uses';
		$number_used       = $this->order_modifier_meta_repository->find_by_order_modifier_id_and_meta_key( $order_modifier_id, $coupons_uses_key );

		// If no available coupons are set, return '-'.
		if ( 0 === $coupons_available ) {
			return '-';
		}

		// Calculate remaining coupons.
		$remaining = $coupons_available - (int) ( $number_used->meta_value ?? 0 );

		return (string) max( $remaining, 0 ); // Ensures no negative values are returned.
	}

	/**
	 * Renders the used column for an order modifier.
	 *
	 * Displays the number of used coupons. If coupons_available is 0 (unlimited), returns '-'.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The order modifier item.
	 *
	 * @return string The number of used coupons, or '-' if unlimited.
	 */
	protected function render_used_column( $item ): string {
		$order_modifier_id = $item->id;

		// Fetch available coupons.
		$coupons_available = $this->get_coupons_available( $order_modifier_id );

		// If no available coupons are set, return '-'.
		if ( 0 === $coupons_available ) {
			return '-';
		}

		// Fetch and return the number of used coupons.
		$coupons_uses_key = 'coupons_uses';
		$number_used      = $this->order_modifier_meta_repository->find_by_order_modifier_id_and_meta_key( $order_modifier_id, $coupons_uses_key );

		return (string) (int) ( $number_used->meta_value ?? 0 );
	}

	/**
	 * Renders the coupons amount column for the current item.
	 *
	 * This method uses the modifier's `display_amount_field` to display the fee amount in the appropriate format
	 * based on the sub_type (e.g., 'flat' or 'percent'). The fee amount is passed in cents and is converted
	 * accordingly.
	 *
	 * @since 5.18.0
	 *
	 * @param object $item The current item being rendered. This should contain `raw_amount` and `sub_type`
	 *     fields.
	 *
	 * @return string The formatted fee amount to be displayed in the table.
	 */
	protected function render_raw_amount_column( $item ) {
		return $this->modifier->display_amount_field( $item->raw_amount, $item->sub_type );
	}

	/**
	 * Define sortable columns.
	 *
	 * @since 5.18.0
	 *
	 * @return array An array of sortable columns.
	 */
	protected function get_sortable_columns() {
		return [
			'display_name' => [ 'display_name', true ],
			'slug'         => [ 'slug', false ],
			'raw_amount'   => [ 'raw_amount', false ],
			'used'         => [ 'used', false ],
			'remaining'    => [ 'remaining', false ],
			'status'       => [ 'status', false ],
		];
	}

	/**
	 * Renders the explanation text for the table.
	 *
	 * This method returns a description related to the current table context, providing users with information
	 * about the functionality of modifiers they are viewing or editing.
	 *
	 * @since 5.18.0
	 *
	 * @return string The explanation text with a clickable "Learn More" link.
	 */
	public function render_table_explain_text(): string {
		$learn_more_link = sprintf(
			'<a href="%s">%s</a>',
			'#', // @todo redscar - need to get the KB article link.
			__( 'Learn More', 'event-tickets' )
		);

		return sprintf(
			'Create a coupon for a discount to be applied at checkout. Coupons can only be used with Tickets Commerce transactions. %s',
			$learn_more_link
		);
	}
}
