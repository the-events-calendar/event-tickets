<?php
/**
 * Coupon Edit Screen for Order Modifiers.
 *
 * This file handles the HTML form rendering for editing or creating a coupon.
 * The form includes fields for coupon name, code, discount type, amount, status, and coupon limit.
 * It also includes a nonce field for security.
 *
 * @since TBD
 *
 * @var string $order_modifier_display_name The coupon name (display name).
 * @var string $order_modifier_slug The coupon code (slug).
 * @var string $order_modifier_sub_type The discount type (percentage/flat).
 * @var int    $order_modifier_fee_amount_cents The amount (in cents).
 * @var string $order_modifier_status The status of the coupon (active, inactive, draft).
 * @var int    $order_modifier_coupon_limit The coupon limit.
 *
 * @package TEC\Tickets\Order_Modifiers
 */

?>

<form method="post" action="">

	<?php wp_nonce_field( 'order_modifier_save_action', 'order_modifier_save_action' ); ?>

	<div>
		<label for="order_modifier_coupon_name"><?php esc_html_e( 'Coupon Name', 'event-tickets' ); ?></label>
		<input type="text" name="order_modifier_coupon_name" id="order_modifier_coupon_name"
			   value="<?php echo esc_attr( $order_modifier_display_name ?? '' ); ?>">
	</div>

	<div>
		<label for="order_modifier_slug"><?php esc_html_e( 'Coupon Code', 'event-tickets' ); ?></label>
		<input type="text" name="order_modifier_slug" id="order_modifier_slug"
			   value="<?php echo esc_attr( $order_modifier_slug ?? '' ); ?>">
	</div>

	<div>
		<label for="order_modifier_sub_type"><?php esc_html_e( 'Discount Type', 'event-tickets' ); ?></label>
		<select name="order_modifier_sub_type" id="order_modifier_sub_type">
			<option
				value="percent" <?php selected( $order_modifier_sub_type ?? '', 'percent' ); ?>><?php esc_html_e( 'Percent Off', 'event-tickets' ); ?></option>
			<option
				value="flat" <?php selected( $order_modifier_sub_type ?? '', 'flat' ); ?>><?php esc_html_e( 'Flat', 'event-tickets' ); ?></option>
		</select>
	</div>

	<div>
		<label for="order_modifier_amount"><?php esc_html_e( 'Amount', 'event-tickets' ); ?></label>
		<input type="number" name="order_modifier_amount" id="order_modifier_amount"
			   value="<?php echo esc_attr( $order_modifier_fee_amount_cents ); ?>">
	</div>

	<div>
		<label for="order_modifier_status"><?php esc_html_e( 'Status', 'event-tickets' ); ?></label>
		<select name="order_modifier_status" id="order_modifier_status">
			<option
				value="active" <?php selected( $order_modifier_status ?? '', 'active' ); ?>><?php esc_html_e( 'Active', 'event-tickets' ); ?></option>
			<option
				value="inactive" <?php selected( $order_modifier_status ?? '', 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'event-tickets' ); ?></option>
			<option
				value="draft" <?php selected( $order_modifier_status ?? '', 'draft' ); ?>><?php esc_html_e( 'Draft', 'event-tickets' ); ?></option>
		</select>
	</div>

	<div>
		<label for="order_modifier_coupon_limit"><?php esc_html_e( 'Coupon Limit', 'event-tickets' ); ?></label>
		<input type="number" name="order_modifier_coupon_limit" id="order_modifier_coupon_limit"
			   value="<?php echo esc_attr( $order_modifier_coupon_limit ?? '' ); ?>">
	</div>

	<div>
		<input
			type="submit"
			id="order_modifier_form_save"
			class="button-primary"
			name="order_modifier_form_save"
			value="<?php echo esc_attr__( 'Save Coupon', 'event-tickets' ); ?>"
		/>
	</div>

</form>
