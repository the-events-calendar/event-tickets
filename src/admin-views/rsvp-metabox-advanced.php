<tr class="<?php $this->tr_class(); ?>">
	<td><label for="ticket_woo_stock"><?php esc_html_e( 'Stock:', 'tribe-tickets' ); ?></label></td>
	<td>
		<input type='text' id='ticket_rsvp_stock' name='ticket_rsvp_stock' class="ticket_field" size='7'
		       value='<?php echo esc_attr( $stock ); ?>'/>

		<p class="description"><?php esc_html_e( "(Total available # of this ticket type. Once they're gone, ticket type is sold out)",
				'tribe-tickets' ); ?></p>
	</td>
</tr>

<?php
if ( class_exists( 'Tribe__Events__Pro__Main' ) ) {
	?>
	<tr class="<?php $this->tr_class(); ?>">
		<td colspan="2" class="tribe_sectionheader updated">
			<p class="warning"><?php _e( 'Currently, Tickets will only show up on the frontend once per full event. For PRO users this means the same ticket will appear across all events in the series. Please configure your events accordingly.',
					'tribe-tickets' ); ?></p>
		</td>
	</tr>
	<?php
}
?>

