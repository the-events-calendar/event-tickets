<div class="input_block <?php $this->tr_class(); ?>">
	<label for="ticket_rsvp_stock" class="ticket_form_left"><?php esc_html_e( 'Stock:', 'event-tickets' ); ?></label>
	<input type='text' id='ticket_rsvp_stock' name='ticket_rsvp_stock' class="ticket_field" size='7' value='<?php echo esc_attr( $stock ); ?>'/>
	<p class="ticket_form_right"><?php esc_html_e( "(Total available # of this ticket type. Once they're gone, ticket type is sold out.)", 'event-tickets' ); ?></p>
</div>

<?php
if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
	if ( is_admin() ) {
		$bumpdown = __( 'Currently, tickets will only show up on the frontend once per full event. For PRO users this means the same ticket will appear across all events in the series. Please configure your events accordingly.', 'event-tickets' );
	} else {
		$bumpdown = __( 'Selling tickets for a recurring event series is not recommended. The tickets you configure will show on all instances of an event series which can be confusing to attendees. Please configure your events carefully.', 'event-tickets' );
	}

	?>
	<div class="<?php $this->tr_class(); ?>">
		<p>
			<?php esc_html_e( 'Selling tickets for recurring events', 'event-tickets' ); ?>
			<span class="tribe-bumpdown-trigger dashicons dashicons-editor-help"
			      data-bumpdown="<?php echo esc_attr( $bumpdown ); ?>"
			      data-bumpdown-class="<?php echo esc_attr( $this->tr_class() ); ?>"></span>
		</p>
	<?php
}
