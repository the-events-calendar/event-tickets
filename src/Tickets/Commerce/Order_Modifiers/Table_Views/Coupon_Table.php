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

use TEC\Tickets\Commerce\Order_Modifiers\Data_Transfer_Objects\Order_Modifier_DTO;
use TEC\Tickets\Commerce\Order_Modifiers\Models\Coupon as Coupon_Model;
use TEC\Tickets\Commerce\Order_Modifiers\Modifiers\Coupon;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Coupons;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifier_Relationship;
use TEC\Tickets\Commerce\Order_Modifiers\Repositories\Order_Modifiers_Meta;
use TEC\Tickets\Commerce\Order_Modifiers\Traits\Coupons as Coupon_Trait;

/**
 * Class for displaying Coupon data in the table.
 *
 * @since 5.18.0
 */
class Coupon_Table extends Order_Modifier_Table {

	use Coupon_Trait;

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
	 * Render the "status" column.
	 *
	 * @since 5.18.0
	 *
	 * @param Coupon_Model $item The current item.
	 *
	 * @return string
	 */
	protected function render_status_column( Coupon_Model $item ) {
		return $this->modifier->get_status_display( $item->status );
	}

	/**
	 * Get the modifier items.
	 *
	 * @since 5.21.0
	 *
	 * @param array $params The query parameters.
	 *
	 * @return array The items that were retrieved.
	 */
	protected function get_items( array $params ): array {
		return array_map(
			function ( $item ) {
				return Order_Modifier_DTO::fromObject( $item )->toModel();
			},
			$this->modifier->get_modifiers( $params, false )
		);
	}

	/**
	 * Renders the remaining column for an order modifier.
	 *
	 * Displays the number of remaining coupons by subtracting the number used from the available coupons.
	 * If the coupons_available is empty or 0, returns '-'.
	 *
	 * @since 5.18.0
	 *
	 * @param Coupon_Model $item The order modifier item.
	 *
	 * @return string The number of remaining coupons, or '-' if unlimited.
	 */
	protected function render_remaining_column( Coupon_Model $item ): string {
		$usage_limit = $this->get_coupon_usage_limit( $item->id );
		if ( -1 === $usage_limit ) {
			return '-';
		}

		$number_used = $this->get_coupon_uses( $item->id );

		// Use max() to ensure we don't return a negative number.
		return (string) max( $usage_limit - $number_used, 0 );
	}

	/**
	 * Renders the used column for an order modifier.
	 *
	 * Displays the number of used coupons. If coupons_available is 0 (unlimited), returns '-'.
	 *
	 * @since 5.18.0
	 *
	 * @param Coupon_Model $item The order modifier item.
	 *
	 * @return string The number of used coupons, or '-' if unlimited.
	 */
	protected function render_used_column( Coupon_Model $item ): string {
		return (string) $this->get_coupon_uses( (int) $item->id );
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
	 * @param Coupon_Model $item The current item being rendered. This should contain `raw_amount` and `sub_type`
	 *     fields.
	 *
	 * @return string The formatted fee amount to be displayed in the table.
	 */
	protected function render_raw_amount_column( Coupon_Model $item ) {
		return $this->modifier->display_amount_field( $item->raw_amount, $item->sub_type );
	}

	/**
	 * Define sortable columns.
	 *
	 * @since 5.18.0
	 *
	 * @return array An array of sortable columns.
	 */
	public function get_sortable_columns() {
		return [
			'display_name' => [ 'display_name', true ],
			'slug'         => [ 'slug', false ],
			'raw_amount'   => [ 'raw_amount', false ],
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
			'<span class="tec-tickets__modifier-explain-text">%s %s</span>',
			esc_html__( 'Create a coupon for a discount to be applied at checkout. Coupons can only be used with Tickets Commerce transactions.', 'event-tickets' ),
			$learn_more_link
		);
	}
}
