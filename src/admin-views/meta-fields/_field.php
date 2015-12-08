<div id="field-<?php echo $field_id; ?>" class="tribe-tickets-attendee-info-active-field postbox closed">
	<div class="handlediv" title="Click to toggle"><br></div>
	<h3 class="hndle ui-sortable-handle"><span><?php echo esc_html( $type_name ); ?>:</span> <?php echo esc_attr( $label ); ?></h3>

	<div class="inside">
		<input type="hidden" class="ticket_field" name="tribe-tickets-input-<?php echo $field_id;?>-type" value="<?php echo esc_attr( $type ); ?>">
		<input type="hidden" class="ticket_field" name="tribe-tickets-input[]" value="<?php echo $field_id; ?>">

		<div class="tribe-tickets-input tribe-tickets-input-text">
			<label for="tickets_attendee_info_field">Label:</label>
			<input type="text" class="ticket_field" name="tribe-tickets-input-<?php echo $field_id;?>-label" value="<?php echo esc_attr( $label ); ?>">
		</div>

		##FIELD_EXTRA_DATA##

		<div class="tribe-tickets-input tribe-tickets-input-checkbox tribe-tickets-required">
			<label class="prompt"><input type="checkbox" <?php checked( $required, 'on' ); ?> class="ticket_field" name="tribe-tickets-input-<?php echo $field_id;?>-required" value="on">
				Required?
			</label>
		</div>
		<div class="tribe-tickets-delete-field">
			<a href="#" class="delete-attendee-field" >Delete this field</a>
		</div>
	</div>
</div>