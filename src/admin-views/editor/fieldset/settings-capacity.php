<?php
$post_id                 = get_the_ID();
$stock                   = Tribe__Tickets__Tickets::get_ticket_counts( $post_id );
$shared_ticket_list      = tribe( 'tickets.handler' )->get_event_shared_tickets( $post_id );
$independent_ticket_list = tribe( 'tickets.handler' )->get_event_independent_tickets( $post_id );
$unlimited_ticket_list   = tribe( 'tickets.handler' )->get_event_unlimited_tickets( $post_id );
$rsvp_ticket_list        = tribe( 'tickets.handler' )->get_event_rsvp_tickets( $post_id );
$total_capacity          = tribe( 'tickets.handler' )->get_total_event_capacity( $post_id );
$total_shared_tickets    = tribe_tickets_get_capacity( $post_id );
?>
<table id="tribe_expanded_capacity_table" summary="capacity table" class="eventtable ticket_list tribe-tickets-editor-capacity-table eventForm tribe-tickets-editor-table striped fixed">
	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-shared">
		<td><?php esc_html_e( 'Shared Capacity:', 'event-tickets-plus' ); ?></td>
		<td>
			<input
				id="settings_global_capacity_edit"
				class="settings_field"
				size="8"
				name="tribe-tickets[settings][event_capacity]"
				value="<?php echo esc_attr( $total_shared_tickets ); ?>"
				aria-label="<?php esc_html_e( 'Global Shared Capacity field', 'event-tickets-plus' ); ?>"
				<?php echo esc_attr( ! is_null( $total_shared_tickets ) ? 'disabled' : '' ); ?>
			/>
			<button
				id="global_capacity_edit_button"
				class="global_capacity_edit_button tribe-button-icon tribe-button-icon-edit"
				title="<?php esc_attr_e( 'Edit Shared Capacity', 'event-tickets-plus' ) ?>"
				aria-controls="settings_global_capacity_edit"
			></button>
		</td>
		<td>
			<?php if ( ! empty( $shared_ticket_list ) ) : ?>
				<span class="tribe_capacity_table_ticket_list"><?php echo esc_html( implode ( ', ', wp_list_pluck( $shared_ticket_list, 'name' ) ) ); ?></span>
			<?php endif; ?>
		</td>
	</tr>

	<?php if ( empty( $independent_ticket_list ) ) : ?>
	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-independent">
		<td><?php esc_html_e( 'Independent Capacity:', 'event-tickets-plus' ); ?></td>
		<td colspan="2">0</td>
	</tr>
	<?php endif; ?>

	<?php foreach ( $independent_ticket_list as $index => $ticket ) : ?>
	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-independent" data-capacity="<?php echo esc_attr( $ticket->capacity() ); ?>">
		<td>
			<?php
			if ( 0 === $index ) {
				esc_html_e( 'Independent Capacity:', 'event-tickets-plus' );
			}
			?>
		</td>
		<td>
			<?php echo esc_html( $ticket->capacity() ); ?>
		</td>
		<td>
			<span class="tribe_capacity_table_ticket_list"><?php echo esc_html( $ticket->name ); ?></span>
		</td>
	</tr>
	<?php endforeach; ?>

	<?php if ( -1 === $total_capacity ) : ?>
	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-unlimited">
		<td><?php esc_html_e( 'Unlimited Capacity:', 'event-tickets-plus' ); ?></td>
		<td>
			<?php echo esc_html( tribe( 'tickets.handler' )->unlimited_term ); ?>
		</td>
		<td>
			<?php if ( ! empty( $unlimited_ticket_list ) ) : ?>
				<span class="tribe_capacity_table_ticket_list">
					<?php echo esc_html( implode( ', ', wp_list_pluck( $unlimited_ticket_list, 'name' ) ) ); ?>
				</span>
			<?php else : ?>
				<span class="tribe_capacity_table_ticket_list"> &mdash; </span>
			<?php endif; ?>
		</td>
	</tr>
	<?php endif; ?>

	<?php foreach ( $rsvp_ticket_list as $index => $ticket ) : ?>
	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-rsvp" data-capacity="<?php echo esc_attr( $ticket->capacity() ); ?>">
		<td>
			<?php
			if ( 0 === $index ) {
				esc_html_e( 'RSVPs:', 'event-tickets-plus' );
			}
			?>
		</td>
		<td>
			<?php tribe_tickets_get_readable_amount( $ticket->capacity(), null, true ); ?>
		</td>
		<td>
			<span class="tribe_capacity_table_ticket_list"><?php echo esc_html( $ticket->name ); ?></span>
		</td>
	</tr>
	<?php endforeach; ?>

	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-total" data-total-capacity="<?php echo esc_attr( $total_capacity ); ?>">
		<td><?php esc_html_e( 'Total Capacity:', 'event-tickets-plus' ); ?></td>
		<td colspan="2" class="tribe-tickets-editor-total-capacity">
			<?php
			if ( ! $total_capacity ) {
				esc_html_e( 'Create a ticket to add event capacity', 'event-tickets-plus' );
			} else {
				tribe_tickets_get_readable_amount( $total_capacity, null, true );
			}
			?>
		</td>
	</tr>
</table>