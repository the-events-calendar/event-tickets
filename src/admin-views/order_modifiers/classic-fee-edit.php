<?php
/**
 * Template for displaying the ticket fees section in the ticket metabox.
 *
 * @since TBD
 *
 * @var int   $post_id The event the ticket is associated to.
 * @var int   $ticket_id The ID of the current ticket (nullable).
 * @var array $related_fee_ids Array of fee IDs associated with the current ticket.
 * @var array $automatic_fees Array of fees that are automatically applied (meta_value is 'all' or empty).
 * @var array $selectable_fees Array of fees that can be manually selected (meta_value is not 'all').
 */

?>

<div class="input_block">
	<label class="ticket_form_label ticket_form_left"
		   for="ticket_fees"><?php esc_html_e( 'Ticket Fees:', 'event-tickets' ); ?></label>
	<div class="ticket_form_right">

		<?php if ( ! empty( $automatic_fees ) || ! empty( $selectable_fees ) ) : ?>

			<!-- Display automatically applied fees if meta_value is 'all' or empty -->
			<?php if ( ! empty( $automatic_fees ) ) : ?>
				<div class="automatic-fees">
					<h4><?php esc_html_e( 'The following fees will be automatically applied:', 'event-tickets' ); ?></h4>
					<ul>
						<?php foreach ( $automatic_fees as $automatic_fee ) : ?>
							<li><?php echo esc_html( $automatic_fee->display_name ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<!-- Display checkboxes for fees that are not 'all' -->
			<?php if ( ! empty( $selectable_fees ) ) : ?>
				<?php foreach ( $selectable_fees as $selectable_fee ) : ?>
					<div class="fee-checkbox">
						<input
							type="checkbox"
							id="fee_<?php echo esc_attr( $selectable_fee->id ); ?>"
							name="ticket_order_modifier_fees[]"
							value="<?php echo esc_attr( $selectable_fee->id ); ?>"
							<?php if ( in_array( $selectable_fee->id, $related_fee_ids ) ) : ?>
								checked="checked"
							<?php endif; ?>
						>
						<label for="fee_<?php echo esc_attr( $selectable_fee->id ); ?>">
							<?php echo esc_html( $selectable_fee->display_name ); ?>
						</label>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>

		<?php else : ?>
			<p><?php esc_html_e( 'No fees available.', 'event-tickets' ); ?></p>
		<?php endif; ?>

	</div>
</div>
