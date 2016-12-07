<?php
$this->attendees_table->prepare_items();

$event_id = $this->attendees_table->event->ID;
$event = $this->attendees_table->event;
$tickets = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
?>

<div class="wrap tribe-attendees-page">
	<?php if ( $this->should_render_title ) : ?>
        <h1><?php esc_html_e( 'Attendees', 'event-tickets' ); ?></h1>
	<?php endif; ?>
	<div id="tribe-attendees-summary" class="welcome-panel">
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
					<h3><?php echo esc_html_x( 'Event Details', 'attendee screen summary', 'event-tickets' ); ?></h3>

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
					<h3><?php echo esc_html_x( 'Attendees By Ticket', 'attendee screen summary', 'event-tickets' ); ?></h3>
					<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_top', $event_id ); ?>

					<ul>
					<?php foreach ( $tickets as $ticket ) { ?>
						<li>
							<strong><?php echo esc_html( $ticket->name ) ?>: </strong>
							<?php echo tribe_tickets_get_ticket_stock_message( $ticket ); ?>
						</li>
					<?php } ?>
					</ul>
					<?php do_action( 'tribe_events_tickets_attendees_ticket_sales_bottom', $event_id );  ?>
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
			</div>
		</div>
	</div>
	<?php do_action( 'tribe_events_tickets_attendees_event_summary_table_after', $event_id ); ?>

	<form id="topics-filter" class="topics-filter" method="post">
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>" />
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>" id="event_id" value="<?php echo esc_attr( $event_id ); ?>" />
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>" value="<?php echo esc_attr( $event->post_type ); ?>" />
		<?php $this->attendees_table->display(); ?>
	</form>
</div>
