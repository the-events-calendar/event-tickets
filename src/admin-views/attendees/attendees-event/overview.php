<?php
/**
 * Event Attendees Summary Ticket Overview template.
 *
 * @since 5.6.5
 * @since 5.8.0 Refactored logic out of the template into the `Tribe__Tickets__Attendees::render` method.
 *
 * @var \Tribe__Template                              $this              Current template object.
 * @var int                                           $event_id          The event/post/page id.
 * @var Tribe__Tickets__Attendees                     $attendees         The Attendees object.
 * @var array<string,Tribe__Tickets__Ticket_Object[]> $tickets_by_type   The tickets grouped by type.
 * @var array<string,string>                          $type_icon_classes A map from ticket types to their icon classes.
 * @var array<string,string>                          $type_labels       A map from ticket types to their labels.
 * @var array{sold: int, available: int}              $ticket_totals     The total number of tickets sold and available.
 */
?>

<div class="welcome-panel-column welcome-panel-middle">
	<h3 class="tec-tickets__admin-attendees-overview-title">
		<?php echo esc_html_x( 'Ticket Overview', 'attendee screen summary', 'event-tickets' ); ?>
	</h3>

	<?php
	/**
	 * Fires before the ticket sales section of the attendees summary.
	 *
	 * @since 5.6.5
	 *
	 * @param int $event_id The ID of the post the overview is being rendered for.
	 */
	do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id );
	?>

	<?php foreach ( $tickets_by_type as $type_name => $type_tickets ) : ?>
		<?php if ( empty( $type_tickets ) ) {
			continue;
		} ?>

		<div class="tec-tickets__admin-attendees-overview-ticket-type">
			<div
				class="tec-tickets__admin-attendees-overview-ticket-type-icon <?php echo esc_attr( $type_icon_classes[ $type_name ] ?? '' ) ?>"></div>
			<div class="tec-tickets__admin-attendees-overview-ticket-type-label">
				<?php esc_html_e( $type_labels[ $type_name ] ?? $type_name ) ?>
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

	<?php
	/**
	 * Fires after the ticket sales section of the attendees summary.
	 *
	 * @since 5.6.5
	 *
	 * @param int $event_id The ID of the post the overview is being rendered for.
	 */
	do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id );
	?>
</div>
