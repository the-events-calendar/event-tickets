<button
	id="ticket_form_toggle"
	class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
	aria-label="<?php echo $add_new_ticket_label ?>"
"<?php echo disabled( count( $ticket_providing_modules ) === 0 ) ?>"
>
<?php
echo esc_html(
	sprintf(
		_x( 'New %s', 'admin editor panel list button label', 'event-tickets' ),
		tribe_get_ticket_label_singular_lowercase( 'admin_editor_panel_list_button_label' )
	)
); ?>
</button>