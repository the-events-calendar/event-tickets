<?php
/** @var Tribe__Tickets__Attendees $attendees */
$attendees = tribe( 'tickets.attendees' );

$attendees->attendees_table->prepare_items();

$event_id = $attendees->attendees_table->event->ID;
$event    = $attendees->attendees_table->event;
$tickets  = Tribe__Tickets__Tickets::get_event_tickets( $event_id );
$pto      = get_post_type_object( $event->post_type );
$singular = $pto->labels->singular_name;

/**
 * Whether or not we should display attendees title
 *
 * @since  4.6.2
 *
 * @param  boolean                          $show_title
 * @param  Tribe__Tickets__Tickets_Handler  $handler
 */
$show_title = apply_filters( 'tribe_tickets_attendees_show_title', true, $attendees );
?>

<div class="wrap tribe-report-page">
	<?php if ( $show_title ) : ?>
		<h1><?php esc_html_e( 'Attendees', 'event-tickets' ); ?></h1>
	<?php endif; ?>
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
					<h3><?php echo esc_html( sprintf( _x( '%s Details', 'attendee screen summary', 'event-tickets' ), $singular ) ); ?></h3>

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
						foreach ( $tickets as $ticket ) {
							/** @var Tribe__Tickets__Ticket_Object $ticket */
							?>
						<li>
							<strong><?php echo esc_html( $ticket->name ) ?>:&nbsp;</strong><?php
							echo esc_html( tribe_tickets_get_ticket_stock_message( $ticket ) );
						?></li>
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
		<?php $attendees->attendees_table->search_box( __( 'Search attendees', 'event-tickets' ), 'attendees-search' ); ?>
		<?php $attendees->attendees_table->display(); ?>
	</form>
</div>
