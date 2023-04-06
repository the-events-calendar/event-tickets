<?php
/**
 * The button to toggle the Series Pass form.
 *
 * @since TBD
 *
 * @var bool $disabled Whether Series Passes can be added to this post or not.
 */

?>

<button
		id="series_pass_form_toggle"
		class="button-secondary ticket_form_toggle tribe-button-icon tribe-button-icon-plus"
		aria-label="<?php echo esc_attr( _x(
				'Add a new Series Pass',
				'ARIA label for the button to add a new Series Pass',
				'event-tickets'
		) ); ?>"
<?php disabled( $disabled ) ?>
>
<?php
echo esc_html(
		sprintf(
				_x( 'New %s', 'Series Pass form toggle button text', 'event-tickets' ),
				tec_tickets_get_series_pass_singular_label()
		)
); ?>
</button>
