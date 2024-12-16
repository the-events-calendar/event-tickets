<?php
/**
 * Template for displaying the ticket fees section in the ticket metabox.
 *
 * @since 5.18.0
 *
 * @var int   $post_id The event the ticket is associated to.
 * @var int   $ticket_id The ID of the current ticket (nullable).
 * @var array $related_fee_ids Array of fee IDs associated with the current ticket.
 * @var array $automatic_fees Array of fees that are automatically applied (meta_value is 'all' or empty).
 * @var array $selectable_fees Array of fees that can be manually selected (meta_value is not 'all').
 */

if ( empty( $automatic_fees ) && empty( $selectable_fees ) ) {
	return;
}
?>

<div class="input_block" id="ticket_order_modifier_ticket_fees">
	<label class="ticket_form_label ticket_form_left" for="ticket_order_modifier_fees">
		<?php esc_html_e( 'Ticket Fees:', 'event-tickets' ); ?>
	</label>
	<div class="ticket_form_right">

		<?php if ( ! empty( $automatic_fees ) || ! empty( $selectable_fees ) ) : ?>

			<?php if ( ! empty( $automatic_fees ) ) : ?>
				<div class="automatic-fees">
					<strong><?php esc_html_e( 'The fees below automatically apply to all tickets.', 'event-tickets' ); ?></strong>
					<?php foreach ( $automatic_fees as $fee ) : ?>
						<div class="ticket_order_modifier_fees ticket_order_modifier_fees--single ticket_order_modifier_fees--automatic">
							<input id="tec-ticket-fees-<?php echo esc_attr( $post_id ); ?>-<?php echo esc_attr( $fee->id ); ?>" value="<?php echo esc_attr( $fee->id ); ?>" type="checkbox" disabled="true" checked="true">
							<label for="tec-ticket-fees-<?php echo esc_attr( $post_id ); ?>-<?php echo esc_attr( $fee->id ); ?>">
								<?php echo esc_html( $fee->display_name . ' (' . $fee->raw_amount . ( $fee->sub_type === 'percent' ? '%' : '' ) . ')' ); ?>
							</label>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $selectable_fees ) ) : ?>
				<div class="selectable-fees">
					<strong><?php esc_html_e( 'Below you can configure specific fees for this ticket.', 'event-tickets' ); ?></strong>
					<div class="ticket_order_modifier_fees ticket_order_modifier_fees--selectable">
						<select
							class="tribe-dropdown"
							name="ticket_order_modifier_fees[]"
							id="ticket_order_modifier_fees"
							multiple
							data-placeholder="<?php esc_attr_e( 'Select fees...', 'event-tickets' ); ?>"
							data-search-placeholder="<?php esc_attr_e( 'Search fees...', 'event-tickets' ); ?>"
							data-allow-clear="true"
							data-dropdown-css-width="false"
							style="width: 100%;"
						>
							<option value=""><?php esc_html_e( 'Select fees', 'event-tickets' ); ?></option>

							<?php foreach ( $selectable_fees as $fee ) : ?>
								<option
									value="<?php echo esc_attr( $fee->id ); ?>" <?php selected( in_array( $fee->id, $related_fee_ids ) ); ?>>
									<?php echo esc_html( $fee->display_name . ' (' . $fee->raw_amount . ( $fee->sub_type === 'percent' ? '%' : '' ) . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<p><?php esc_html_e( 'No fees available.', 'event-tickets' ); ?></p>
		<?php endif; ?>

	</div>
</div>
