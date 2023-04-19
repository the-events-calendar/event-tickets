<button
	id="rsvp_form_toggle"
	class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
	aria-label="<?php echo esc_attr(sprintf(_x('Add a new %s', 'RSVP form toggle button label', 'event-tickets'), tribe_get_rsvp_label_singular('rsvp_form_toggle_button_label'))); ?>"
>
	<?php
	echo esc_html(
		sprintf(
			_x('New %s', 'RSVP form toggle button text', 'event-tickets'),
			tribe_get_rsvp_label_singular('rsvp_form_toggle_button_text')
		)
	); ?>
</button>
