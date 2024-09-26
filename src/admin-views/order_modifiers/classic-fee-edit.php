<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="ticket_fees">Ticket Fees:</label>
	<div class="ticket_form_right">
		<?php if ( ! empty( $fees ) ) : ?>
			<?php foreach ( $fees as $fee ) : ?>
				<div class="fee-checkbox">
					<input
						type="checkbox"
						id="fee_<?php echo esc_attr( $fee->id ); ?>"
						name="ticket_order_modifier_fees[]"
						value="<?php echo esc_attr( $fee->id ); ?>"
						<?php if ( $fee->meta_value === 'all' || $fee->meta_value === null ) : ?>
							checked="checked"
						<?php endif; ?>
					>
					<label for="fee_<?php echo esc_attr( $fee->id ); ?>">
						<?php echo esc_html( $fee->display_name ); ?>
					</label>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No fees available.', 'event-tickets' ); ?></p>
		<?php endif; ?>
	</div>
</div>
