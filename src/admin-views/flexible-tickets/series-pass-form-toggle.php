<?php
/**
 * The button to toggle the Series Pass form.
 *
 * @since 5.8.0
 *
 * @var bool $disabled Whether Series Passes can be added to this post or not.
 */
?>

<button
		id="series_pass_form_toggle"
		class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
		aria-label="<?php echo esc_attr(
				sprintf(
						// Translators: %s is the singular uppercase name of the Series Pass ticket type.
						_x(
								'Add a new %s',
								'ARIA label for the button to add a new Series Pass',
								'event-tickets'
						),
						tec_tickets_get_series_pass_singular_uppercase( 'admin_ticket_new_button_aria_label' )
				)
		); ?>"
		<?php disabled( $disabled ) ?>
		data-ticket-type="series_pass"
>
	<?php
	echo esc_html(
			sprintf(
					// Translators: %s is the singular lowercase name of the Series Pass ticket type.
					_x(
							'New %s',
							'Series Pass form toggle button text',
							'event-tickets'
					),
					tec_tickets_get_series_pass_singular_uppercase( 'admin_ticket_new_button_text' )
			)
	); ?>
</button>
