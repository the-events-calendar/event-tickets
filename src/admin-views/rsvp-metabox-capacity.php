<div
	class="input_block ticket_advanced_Tribe__Tickets__RSVP tribe-dependent"
	data-depends="#Tribe__Tickets__RSVP_radio"
	data-condition-is-checked
>
	<label
		for="Tribe__Tickets__RSVP_capacity"
		class="ticket_form_label ticket_form_left"
	>
		<?php esc_html_e( 'Capacity:', 'event-tickets' ); ?>
	</label>
	<input
		type='text' id='Tribe__Tickets__RSVP_capacity'
		name='tribe-ticket[capacity]'
		class="ticket_field tribe-rsvp-field-capacity ticket_form_right"
		size='7'
		value='<?php echo esc_attr( -1 === (int) $capacity ? '' : $capacity ); ?>'
	/>
	<span class="tribe_soft_note ticket_form_right"><?php esc_html_e( 'Leave blank for unlimited', 'event-tickets' ); ?></span>
</div>
