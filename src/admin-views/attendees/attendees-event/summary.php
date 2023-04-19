<?php
/**
 * Event Attendees Summary template.
 *
 * @since  5.5.9
 *
 * @var Tribe_Template            $this      Current template object.
 * @var int                       $event_id  The event/post/page id.
 * @var Tribe__Tickets__Attendees $attendees The Attendees object.
 */

$event    = $attendees->attendees_table->event;
$pto      = get_post_type_object( $event->post_type );
$singular = $pto->labels->singular_name;
$tickets  = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
?>
<div id="tribe-attendees-summary" class="welcome-panel tribe-report-panel">
	<div class="welcome-panel-content">
		<div class="welcome-panel-column-container">

			<?php
			/**
			 * Fires before the individual panels within the attendee screen summary
			 * are rendered.
			 *
			 * @param int $event_id
			 */
			do_action( 'tribe_events_tickets_attendees_event_details_top', $event_id );
			?>

			<div class="welcome-panel-column welcome-panel-first">
				<h3>
					<?php
					echo esc_html(
						sprintf(
							// Translators: %s The post type for the attendees report summary.
							_x( '%s Details', 'attendee screen summary', 'event-tickets' ),
							$singular
						)
					);
					?>
				</h3>

				<ul>
					<?php
					/**
					 * Provides an action that allows for the injections of fields at the top of the event details meta ul
					 *
					 * @var $event_id
					 */
					do_action( 'tribe_tickets_attendees_event_details_list_top', $event_id );

					/**
					 * Provides an action that allows for the injections of fields at the bottom of the event details meta ul
					 *
					 * @var $event_id
					 */
					do_action( 'tribe_tickets_attendees_event_details_list_bottom', $event_id );
					?>
				</ul>
				<?php
				/**
				 * Provides an opportunity for various action links to be added below
				 * the event name, within the attendee screen.
				 *
				 * @param int $event_id
				 */
				do_action( 'tribe_tickets_attendees_do_event_action_links', $event_id );

				/**
				 * Provides an opportunity for various action links to be added below
				 * the action links
				 *
				 * @param int $event_id
				 */
				do_action( 'tribe_events_tickets_attendees_event_details_bottom', $event_id ); ?>

			</div>
			<div class="welcome-panel-column welcome-panel-middle">
				<h3><?php echo esc_html_x( 'Overview', 'attendee screen summary', 'event-tickets' ); ?></h3>
				<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id ); ?>

				<ul>
					<?php
					/** @var Tribe__Tickets__Ticket_Object $ticket */
					foreach ( $tickets as $ticket ) {
						$ticket_name = sprintf( '%s [#%d]', $ticket->name, $ticket->ID );
						?>
						<li>
							<strong><?php echo esc_html( $ticket_name ) ?>:&nbsp;</strong><?php
							echo esc_html( tribe_tickets_get_ticket_stock_message( $ticket ) );

							/**
							 * Adds an entry point to inject additional info for ticket.
							 *
							 * @since 5.0.3
							 */
							$this->set( 'ticket_item_for_overview', $ticket );
							$this->do_entry_point( 'overview_section_after_ticket_name' );
							?>
						</li>
					<?php } ?>
				</ul>
				<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id ); ?>
			</div>
			<div class="welcome-panel-column welcome-panel-last alternate">
				<?php
				/**
				 * Fires before the main body of attendee totals are rendered.
				 *
				 * @param int $event_id
				 */
				do_action( 'tribe_events_tickets_attendees_totals_top', $event_id );

				/**
				 * Trigger for the creation of attendee totals within the attendee
				 * screen summary box.
				 *
				 * @param int $event_id
				 */
				do_action( 'tribe_tickets_attendees_totals', $event_id );

				/**
				 * Fires after the main body of attendee totals are rendered.
				 *
				 * @param int $event_id
				 */
				do_action( 'tribe_events_tickets_attendees_totals_bottom', $event_id );
				?>
			</div>
			<?php
			/**
			 * Fires after the last column so that "extra" content can be displayed.
			 *
			 * @since 5.3.4
			 *
			 * @param int $event_id Event ID.
			 */
			do_action( 'tec_tickets_attendees_event_summary_table_extra', $event_id );
			?>
		</div>
	</div>
</div>
<?php do_action( 'tribe_events_tickets_attendees_event_summary_table_after', $event_id ); ?>
