<?php
/**
 * Fee Edit Screen for Order Modifiers.
 *
 * This file handles the HTML form rendering for editing or creating a Fee.
 * The form includes fields for Fee name, code, discount type, amount, status, and Fee limit.
 * It also includes a nonce field for security.
 *
 * @since   5.18.0
 *
 * @var string $order_modifier_display_name     The Fee name (display name).
 * @var string $order_modifier_slug             The Fee code (slug).
 * @var string $order_modifier_sub_type         The discount type (percentage/flat).
 * @var int    $order_modifier_fee_amount_cents The amount (in cents).
 * @var string $order_modifier_status           The status of the Fee (active, inactive, draft).
 * @var int    $order_modifier_fee_limit        The Fee limit.
 * @var string $order_modifier_apply_to         What the fee is applied to (All, Per, Organizer, Venue)
 *
 * @package TEC\Tickets\Commerce\Order_Modifiers
 *
 * phpcs:disable WordPress.WP.GlobalVariablesOverride
 */

/**
 * Util function to display the validation error according to the field name.
 *
 * @param string $field_label Field label.
 *
 * @return string
 */
$get_validation_error_attr = function ( string $field_label ): string {
	// translators: %s is the field label.
	return sprintf( __( '%s is required', 'event-tickets' ), $field_label );
};

if ( ! empty( $order_modifier_display_name ) ) {
	$heading = __( 'Edit Fee', 'event-tickets' );
} else {
	$heading = __( 'New Fee', 'event-tickets' );
}

$modifier_statuses = [
	'active'   => __( 'Active', 'event-tickets' ),
	'inactive' => __( 'Inactive', 'event-tickets' ),
	'draft'    => __( 'Draft', 'event-tickets' ),
];

?>
<div class="wrap">
	<h1><?php echo esc_html( $heading ); ?></h1>
	<div class="form-wrap">

		<form method="post" class="tribe-validation tec-settings-order_modifier">
			<div class="tribe-settings-form-wrap">

				<?php wp_nonce_field( 'order_modifier_save_action', 'order_modifier_save_action' ); ?>

				<!-- Error Notice Section -->
				<div class="tribe-notice tribe-notice-validation notice-error is-dismissible">
					<!-- Error messages will be appended here -->
				</div>

				<div class="form-field form-required">
					<label for="order_modifier_display_name">
						<?php esc_html_e( 'Fee Name', 'event-tickets' ); ?>
					</label>
					<input
						type="text"
						name="order_modifier_display_name"
						id="order_modifier_display_name"
						class="tribe-field tribe-validation-field"
						maxlength="255"
						data-validation-required="true"
						data-validation-error="<?php echo esc_attr( $get_validation_error_attr( __( 'Fee Name', 'event-tickets' ) ) ); ?>"
						value="<?php echo esc_attr( $order_modifier_display_name ?? '' ); ?>" />
					<p><?php esc_html_e( 'This fee name will display in the cart at checkout.', 'event-tickets' ); ?></p>
				</div>

				<input
					type="hidden"
					name="order_modifier_slug"
					id="order_modifier_slug"
					class="tribe-field"
					value="<?php echo esc_attr( $order_modifier_slug ?? '' ); ?>" />

				<div class="form-field form-required">
					<label for="order_modifier_sub_type">
						<?php esc_html_e( 'Fee Type', 'event-tickets' ); ?>
					</label>
					<select
						name="order_modifier_sub_type"
						id="order_modifier_sub_type"
						class="tribe-validation-field"
						data-validation-required="true"
						data-validation-error="<?php echo esc_attr( $get_validation_error_attr( __( 'Fee Type', 'event-tickets' ) ) ); ?>">
						<option value="percent" <?php selected( $order_modifier_sub_type ?? '', 'percent' ); ?>>
							<?php esc_html_e( 'Percent', 'event-tickets' ); ?>
						</option>
						<option value="flat" <?php selected( $order_modifier_sub_type ?? '', 'flat' ); ?>>
							<?php esc_html_e( 'Flat', 'event-tickets' ); ?>
						</option>
					</select>
				</div>

				<div class="form-field form-required">
					<label for="order_modifier_amount"><?php esc_html_e( 'Amount', 'event-tickets' ); ?></label>
					<input
						type="number"
						name="order_modifier_amount"
						id="order_modifier_amount"
						class="tribe-field tribe-validation-field tec_order_modifier_amount_field"
						step="0.01"
						maxlength="9"
						data-validation-required="true"
						data-validation-is-greater-than="0"
						data-validation-error="<?php echo esc_attr( $get_validation_error_attr( __( 'Amount', 'event-tickets' ) ) ); ?>"
						value="<?php echo esc_attr( $order_modifier_fee_amount_cents ); ?>" />
				</div>

				<div class="form-field form-required">
					<label for="order_modifier_status">
						<?php esc_html_e( 'Status', 'event-tickets' ); ?>
					</label>
					<select
						name="order_modifier_status"
						id="order_modifier_status"
						class="tribe-validation-field"
						data-validation-required="true"
						data-validation-error="<?php echo esc_attr( $get_validation_error_attr( __( 'Status', 'event-tickets' ) ) ); ?>">

						<?php foreach ( $modifier_statuses as $status => $label ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $order_modifier_status ?? '', $status ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>

					</select>
				</div>

				<div class="form-field form-required">
					<label for="order_modifier_apply_to">
						<?php esc_html_e( 'Apply fee to', 'event-tickets' ); ?>
					</label>
					<select
						name="order_modifier_apply_to"
						id="order_modifier_apply_to"
						class="tribe-validation-field"
						data-validation-required="true"
						data-validation-error="<?php echo esc_attr( $get_validation_error_attr( __( 'Apply fee to', 'event-tickets' ) ) ); ?>">
						<option value="per" <?php selected( $order_modifier_apply_to, 'per' ); ?>>
							<?php esc_html_e( 'Set per ticket', 'event-tickets' ); ?>
						</option>
						<option value="all" <?php selected( $order_modifier_apply_to, 'all' ); ?>>
							<?php esc_html_e( 'All tickets', 'event-tickets' ); ?>
						</option>
					</select>
					<p>
						<?php esc_html_e( 'Select a group to apply this fee to tickets automatically. This can be overridden on a per ticket basis during ticket creation.', 'event-tickets' ); ?>
					</p>
				</div>
				<p class="submit">
					<input
						type="submit"
						id="order_modifier_form_save"
						class="button-primary tribe-validation-submit"
						name="order_modifier_form_save"
						value="<?php esc_attr_e( 'Save Fee', 'event-tickets' ); ?>"
					/>
				</p>
			</div>
		</form>
	</div>
</div>
