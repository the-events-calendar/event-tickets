<?php
/**
 * Event Attendees Summary Ticket Overview template.
 *
 * @since  5.6.5
 *
 * @var \Tribe__Template          $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

$tickets  = Tribe__Tickets__Tickets::get_event_tickets( $event_id );

$single_label = __( 'Single Tickets', 'event-tickets' );
$rsvp_label   = __( 'RSVP', 'event-tickets' );
$pass_label   = __( 'Series Passes', 'event-tickets' );

$type_icon_class = [
	$single_label => 'tec-tickets__admin-attendees-overview-ticket-type-icon--ticket',
	$rsvp_label   => 'tec-tickets__admin-attendees-overview-ticket-type-icon--rsvp',
	$pass_label   => 'tec-tickets__admin-attendees-overview-ticket-type-icon--pass',
];

$tickets_by_types = [];
foreach ( $tickets as $ticket ) {
	$type = $single_label;
	if ( Tribe__Tickets__RSVP::class === $ticket->provider_class ) {
		$type = $rsvp_label;
	}
	// @todo Add logic to determine if a ticket is a pass.
	if ( ! isset( $tickets_by_types[ $type ] ) ) {
		$tickets_by_types[ $type ] = [];
	}
	$tickets_by_types[ $type ][] = $ticket;
}
$ticket_totals = [
	'sold'      => 0,
	'available' => 0,
];
foreach ( $tickets_by_types as $type_name => $type_tickets ) {
	foreach ( $type_tickets as $ticket ) {
		$ticket_totals['sold']      += $ticket->qty_sold();
		if ( $ticket_totals['available'] > -1 ) {
			if ( -1 === $ticket->available() ) {
				$ticket_totals['available'] = -1;
			} else {
				$ticket_totals['available'] += $ticket->available();
			}
		}
	}
}

?>
<div class="welcome-panel-column welcome-panel-middle">
	<h3 class="tec-tickets__admin-attendees-overview-title">
		<?php echo esc_html_x( 'Ticket Overview', 'attendee screen summary', 'event-tickets' ); ?>
	</h3>
	<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id ); ?>
	<?php foreach ( $tickets_by_types as $type_name => $type_tickets ) : ?>
		<div class="tec-tickets__admin-attendees-overview-ticket-type">
			<div class="tec-tickets__admin-attendees-overview-ticket-type-icon <?php echo esc_attr( $type_icon_class[ $type_name ] ) ?>"></div>
			<div class="tec-tickets__admin-attendees-overview-ticket-type-label">
				<?php esc_html_e( $type_name ) ?>
			</div>
			<div class="tec-tickets__admin-attendees-overview-ticket-type-border"></div>
		</div>
		<ul class="tec-tickets__admin-attendees-overview-ticket-type-list" >
			<?php
			/** @var Tribe__Tickets__Ticket_Object $ticket */
			foreach ( $type_tickets as $ticket ) {
				$ticket_name = sprintf( '%s [#%d]', $ticket->name, $ticket->ID );
				?>
				<li class="tec-tickets__admin-attendees-overview-ticket-type-list-item">
					<div>
						<span class="tec-tickets__admin-attendees-overview-ticket-type-list-item-ticket-name">
							<?php esc_html_e( $ticket->name ); ?>
						</span>
						<span class="tec-tickets__admin-attendees-overview-ticket-type-list-item-ticket-id">
							<?php esc_html_e( sprintf( '#%d', $ticket->ID ) ); ?>
						</span>
					</div>
					<div class="tec-tickets__admin-attendees-overview-ticket-type-list-item-stat">
						<?php
							echo esc_html( tribe_tickets_get_ticket_stock_message( $ticket, __( 'issued', 'event-tickets' ) ) );

							/**
							 * Adds an entry point to inject additional info for ticket.
							 *
							 * @since 5.0.3
							 */
							$this->set( 'ticket_item_for_overview', $ticket );
							$this->do_entry_point( 'overview_section_after_ticket_name' );
						?>
					</div>
				</li>
			<?php } ?>
		</ul>
	<?php endforeach; ?>
	<div class="tec-tickets__admin-attendees-overview-ticket-totals">
		<div class="tec-tickets__admin-attendees-overview-ticket-totals-title">
			<?php esc_html_e( 'Total', 'event-tickets' ); ?>
		</div>
		<div class="tec-tickets__admin-attendees-overview-ticket-totals-stat">
			<span>
				<?php
					echo sprintf(
						// Translators: %1$s is the number of tickets issued.
						__( '%s issued', 'event-tickets'),
						esc_html_e( $ticket_totals['sold'] )
					);
				?>
			</span>
			<span>
				<?php
					echo sprintf(
						// Translators: %1$s is the number of tickets available.
						__( '(%s available)', 'event-tickets'),
						tribe_tickets_get_readable_amount( $ticket_totals['available'] )
					);
				?>
			</span>
		</div>
	</div>
	<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id ); ?>
</div>