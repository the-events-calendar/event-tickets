<?php
/**
 * The template for the rsvp limit field.
 *
 * @since TBD
 *
 * @version TBD
 *
 * @var string $rsvp_limit                      The ticket name.
 * @var string $rsvp_required_type_error_message The RSVP required type error message.
 */

?>
<div class="input_block">
	<label class="ticket_form_label ticket_form_left" for="rsvp_limit">
		<?php echo esc_html_x( 'Limit:', 'RSVP limit for an event.', 'event-tickets' ); ?>
	</label>
	<input
		type='text'
		id='rsvp_limit'
		name='rsvp_limit'
		class="ticket_field ticket_form_right"
		size='25'
		value="<?php echo esc_attr( $rsvp_limit ); ?>"
		data-validation-is-required
		data-validation-error="<?php echo esc_attr( $rsvp_required_type_error_message ); ?>"
	/>
	<p class="description ticket_form_right">
		<?php echo esc_html_x( 'Leave blank for unlimited', 'price description', 'event-tickets' ); ?>
	</p>
</div>
